<?php

namespace App\Http\Controllers;

use App\Role;
use App\Subscription;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Laracasts\Flash\Flash;

class SubscriptionController extends Controller
{
    public function subscribe(Request $request) {
        // Custom validator here, so errors can be stored in a named bag, for flexible display options
        $validator = Validator::make($request->all(), [
            'title' => 'required|max:190',
            'email' => 'required|email',
            'confirm' => 'required'
        ],[         // custom messages
            'confirm.required' => 'Die Bedingungen zum Datenschutz müssen gelesen und akzeptiert werden.'
        ]);

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
            $subscription->query = $request->input('url'); // todo change this to optimized query string (optimized = dont store parameters with default (empty) values)
            $subscription->save();

            $user->sendSubscriptionVerificationNotification($subscription);

            Flash::success('Benachrichtigung wurde erstellt. Um die Benachrichtigung zu aktivieren, klicken Sie auf den Bestätigungs-Link den Sie in Kürze von uns per E-Mail erhalten. Sollten Sie keine E-Mail erhalten haben überprüfen Sie bitte Ihren Spam-Ordner.');

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

        return back();
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
}
