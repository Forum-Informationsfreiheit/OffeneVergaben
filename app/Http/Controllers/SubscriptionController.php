<?php

namespace App\Http\Controllers;

use App\Models\Dataset;
use App\Http\Filters\DatasetFilter;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Support\PublicMessages as Msg;

class SubscriptionController extends Controller
{
    const ALL = 'all';

    /**
     * Subscribe is called by the form on the (cached) aufträge page via fetch and answered with JSON:
     * { success: bool, message: string, errors: { field: [string] } }
     * Without JavaScript the same result is rendered as a page.
     */
    public function subscribe(Request $request) {
        // Custom validator here, so errors can be stored in a named bag, for flexible display options
        $validator = Validator::make($request->all(), [
            'title' => 'required|max:190',
            'email' => 'required|email',
            'confirm' => 'required',
            'query' => 'required',
        ],[         // custom messages
            'confirm.required' => Msg::text(Msg::SUBSCRIBE_CONFIRM_REQUIRED)
        ]);

        // "Protective Measure" Honeypot: if the honeypot field is included ignore the request
        // The input field is hidden with a combination of JS and CSS:
        // On pageload a (static) css class is added to the input that hides (display:nones) the field
        if ($request->input('pm_title')) {
            usleep(rand(1,250000)); // add a tiny bit of fake execution time on top (max 0.25s)
            return $this->subscribeResponse($request, true, Msg::text(Msg::SUBSCRIBE_CREATED));
        }

        // Check the query string for sanity
        if (!$this->validateSubscriptionQuery($request->input('query'))) {
            return $this->subscribeResponse($request, false, Msg::text(Msg::SUBSCRIBE_INVALID_QUERY), 422);
        }

        // check uniqueness of querystring for this email
        $validator->after(function($validator) use($request) {
            $subscriber = User::where('email',$request->input('email'))->first();
            if ($subscriber && Subscription::where('user_id',$subscriber->id)->where('query',$request->input('query'))->first()) {
                $validator->errors()->add('query', Msg::text(Msg::SUBSCRIBE_ALREADY_EXISTS));
            }
        });

        // Stop right here if validation fails
        if ($validator->fails()) {
            return $this->subscribeResponse($request, false, null, 422, $validator->errors()->toArray());
        }

        // Looking good
        // Optional: store new user (if new email)
        try {
            Log::info('Subscribe request received.',[
                'email' => $request->input('email'),
                'title' => $request->input('title'),
                'query' => $request->input('query'),
            ]);

            $user = User::where('email',$request->input('email'))->first();
            if (!$user) {
                $user = new User();
                $user->name  = 'Unbekannt';
                $user->email = $request->input('email');
                $user->role_id = Role::SUBSCRIBER;
                $user->password = '';
                $user->save();
            }

            // Store new subscription
            $subscription = new Subscription();
            $subscription->user_id = $user->id;
            $subscription->type = Subscription::TYPE_DATASET; // fixed atm, could be parameter from form
            $subscription->title = $request->input('title');
            $subscription->query = $request->input('query');
            $subscription->save();

            $user->sendSubscriptionVerificationNotification($subscription);

            return $this->subscribeResponse($request, true, Msg::text(Msg::SUBSCRIBE_CREATED));
        } catch(\Exception $ex) {
            Log::error('Exception occured on SubscriptionController:subscribe',[
                'message' => $ex->getMessage(),
                'file' => $ex->getFile(),
                'line' => $ex->getLine(),
                'trace' => $ex->getTraceAsString(),
            ]);

            if (!app()->environment('production')) {
                // rethrow in case of dev/test
                throw $ex;
            }

            return $this->subscribeResponse($request, false, Msg::text(Msg::SUBSCRIBE_FAILED), 500);
        }
    }

    /**
     * @param array $errors field => [messages], the messages are listed above the form
     */
    protected function subscribeResponse(Request $request, bool $success, ?string $message, int $status = 200, array $errors = []) {
        if ($request->expectsJson()) {
            return response()->json(compact('success', 'message', 'errors'), $status);
        }

        return response()->view('public.subscriptions.result', [
            'success' => $success,
            'messages' => $message ? [$message] : array_merge(...array_values($errors)),
        ], $status);
    }

    /**
     * Signed route!
     *
     * Verify is called directly from a subscription verification email via a signed link.
     * On successful verification the user sees a confirmation page (no redirect/flash: public pages have no session).
     *
     * @param $id
     * @param $email
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function verify($id, $email) {
        $subscription = Subscription::findOrFail($id);
        $subscriber   = User::where('email',$email)->first();

        if ($email != $subscriber->email) {
            abort(404);
        }

        $now = Carbon::now();

        $subscription->verified_at = $now;
        $subscription->save();

        if (!$subscriber->email_verified_at) {
            $subscriber->email_verified_at = $now;
            $subscriber->save();
        }

        Log::info('Subscription verified.',[
            'subscription_id' => $id,
            'email' => $email,
        ]);

        return $this->resultView(Msg::text(Msg::VERIFY_SUCCESS, ['title' => $subscription->title]));
    }

    /**
     * Signed route!
     *
     * Cancel is called directly from a subscription update notification email via a signed link.
     * User will be presented a confirmation view to confirm the cancelling of the subscription.
     *
     * @param $id
     * @param $email
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function cancel($id, $email) {
        if ($id == 'all') {
            return $this->cancelAll($email);
        }

        $subscription = Subscription::findOrFail($id);
        $subscriber   = User::where('email',$email)->first();

        if ($email != $subscriber->email) {
            abort(404);
        }

        return view('public.subscriptions.cancel',compact('subscription'));
    }

    /**
     * Route not available for user roles other than SUBSCRIBER
     * or in other words: make sure admins don't accidentally delete themselves
     *
     * @param $email
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    protected function cancelAll($email) {
        $subscriber = User::where('email',$email)->where('role_id',Role::SUBSCRIBER)->first();
        $subscriptions = $subscriber->subscriptions()->orderBy('title','asc')->get();

        if (!$subscriber) {
            abort(404);
        }

        return view('public.subscriptions.cancel-all',compact('subscriber','subscriptions'));
    }

    /**
     * Signed route!
     *
     * Actually unsubscribe.
     *
     * @param $id
     * @param $email
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function unsubscribe($id, $email) {
        if ($id == self::ALL) {
            return $this->unsubscribeAll($email);
        }

        $subscription = Subscription::findOrFail($id);
        $subscriber   = User::where('email',$email)->first();

        if ($email != $subscriber->email) {
            abort(404);
        }

        $title = $subscription->title;
        $subscriber = $subscription->email;
        $query = $subscription->query;

        // DELETE
        DB::table('subscriptions')->where('id', $id)->delete();

        // keep info in log file
        Log::info('Subscription deleted by subscriber.',['subscriber' => $subscriber, 'title' => $title, 'query' => $query ]);

        return $this->resultView(Msg::text(Msg::UNSUBSCRIBE_SUCCESS, ['title' => $title]));
    }

    /**
     * On unsubscribe also delete the subscriber (user)
     *
     * @param $email
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    protected function unsubscribeAll($email) {
        $subscriber = User::where('email',$email)->first();

        if (!$subscriber) {
            abort(404);
        }

        // keep info in log file
        Log::info('Unsubscribe all request received.',[ 'subscriber' => $subscriber->email ]);

        foreach($subscriber->subscriptions as $subscription) {
            // keep info in log file
            Log::info('Delete subscription through unsubscribe all.',['title' => $subscription->title, 'query' => $subscription->query ]);
        }

        // DELETE the user here, but only iff the users role is subscriber
        if ($subscriber->isSubscriber()) {
            $subscriber->delete();
        }

        Log::info('Subscriber and all subscriptions deleted.',[ 'subscriber' => $subscriber->email ]);

        return $this->resultView(Msg::text(Msg::UNSUBSCRIBE_ALL_SUCCESS));
    }

    protected function resultView(string $message) {
        return view('public.subscriptions.result', ['success' => true, 'messages' => [$message]]);
    }

    /**
     * @param $queryString
     * @return bool
     */
    protected function validateSubscriptionQuery($queryString) {
        // all the logic of validating (and empty/invalid filter filtering)
        // already exists in App\DatasetFilter. make use of that
        $pseudoRequest = Request::create(route('public::auftraege').'?'.$queryString);
        $filters = new DatasetFilter($pseudoRequest);
        $filters->apply(Dataset::query());

        $appliedFilters = $filters->getAppliedFilters();
        ksort($appliedFilters);

        return $queryString == http_build_query($appliedFilters);
    }
}
