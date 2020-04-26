<?php

namespace App\Http\Controllers;

use App\Dataset;
use App\Http\Filters\DatasetFilter;
use App\Role;
use App\Subscription;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Laracasts\Flash\Flash;

class SubscriptionController extends Controller
{
    public function subscribe(Request $request) {
        // Custom validator here, so errors can be stored in a named bag, for flexible display options
        $validator = Validator::make($request->all(), [
            'title' => 'required|max:190',
            'email' => 'required|email',
            'confirm' => 'required',
            'query' => 'required',
        ],[         // custom messages
            'confirm.required' => 'Die Bedingungen zum Datenschutz müssen gelesen und akzeptiert werden.'
        ]);

        // Check the query string for sanity
        if (!$this->validateSubscriptionQuery($request->input('query'))) {
            Flash::error('Fehler bei Filterabfrage.');
            return back();
        }

        // check uniqueness of querystring for this email
        $validator->after(function($validator) use($request) {
            $subscriber = User::where('email',$request->input('email'))->first();
            if ($subscriber && Subscription::where('user_id',$subscriber->id)->where('query',$request->input('query'))->first()) {
                $validator->errors()->add('query','Eine Benachrichtigung für diese Abfrage existiert bereits.');
            }
        });

        // Stop right here if validation fails
        if ($validator->fails()) {
            return back()->withErrors($validator,'subscription')->withinput();
        }

        // Looking good
        // Optional: store new user (if new email)
        try {
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

            Flash::success('Benachrichtigung wurde erstellt. Um die Benachrichtigung zu aktivieren, klicken Sie auf den Bestätigungs-Link den Sie in Kürze von uns per E-Mail erhalten. Sollten Sie keine E-Mail erhalten haben überprüfen Sie bitte Ihren Spam-Ordner.');

            return back()->with(['subscribed' => true]);
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

            Flash::error('Beim Erstellen der Benachrichtigung ist ein Fehler aufgetreten.');
        }

        return back()->with(['subscribed' => false]);
    }

    public function verify($id, $email) {
        $subscription = Subscription::findOrFail($id);
        $subscriber   = User::where('email',$email)->first();

        if ($email != $subscriber->email) {
            throw new \RuntimeException("Notification email and Subscriber email don't match.");
        }

        $now = Carbon::now();

        $subscription->verified_at = $now;
        $subscription->save();

        if (!$subscriber->email_verified_at) {
            $subscriber->email_verified_at = $now;
            $subscriber->save();
        }

        Flash::success('Abonnement '.$subscription->title.' wurde bestätigt. ');

        return redirect(route('public::auftraege'));  // TODO where to redirect ???
    }

    public function unsubscribe() {
        dump('unsubscribe');
        // todo validate GET signed url and actually unsubscribe
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
