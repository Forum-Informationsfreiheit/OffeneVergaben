<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Laracasts\Flash\Flash;

class SubscriptionController extends Controller
{
    public function index(Request $request) {

        $query = Subscription::query();

        if ($request->has('subscriber_id') && is_numeric($request->input('subscriber_id'))) {
            $query->where('user_id',$request->input('subscriber_id'));
        }

        $total = $query->count();

        $subscriptions = $query->paginate(50);

        return view('admin.subscriptions.index',compact('subscriptions','total'));
    }

    public function resendVerificationNotification(Request $request) {

        $this->authorize('resend-subscription-verification-notification');

        $subscription = Subscription::findOrFail($request->input('id'));

        $subscription->subscriber->sendSubscriptionVerificationNotification($subscription);

        Flash::info('Benachrichtigung wurde an '.$subscription->subscriber->email.' gesendet.');

        return back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param $request
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        $this->authorize('delete-subscription');

        $id = $request->input('id');

        $subscription = Subscription::findOrFail($id);
        $title = $subscription->subscriber->email . ' - ' . $subscription->title;

        // just do it right here:
        DB::table('subscriptions')->where('id', $id)->delete();

        Flash::success(__("admin.subscriptions.deleted", [ 'title' => $title ]));

        return redirect(route('admin::subscriptions'));
    }
}
