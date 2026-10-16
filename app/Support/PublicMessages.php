<?php

namespace App\Support;

/**
 * Catalog of all user facing result messages of the public routes (subscriptions, downloads).
 *
 * Public pages are cacheable and have no session, so there are no flash messages: the response of an action shows
 * its result itself. Controllers take their texts from here, the overview in /test/messages (non production)
 * lists them with the place where each one is shown.
 */
class PublicMessages
{
    const LEVEL_SUCCESS = 'success';
    const LEVEL_ERROR   = 'danger';

    const SUBSCRIBE_CREATED          = 'subscribe.created';
    const SUBSCRIBE_FAILED           = 'subscribe.failed';
    const SUBSCRIBE_INVALID_QUERY    = 'subscribe.invalid_query';
    const SUBSCRIBE_CONFIRM_REQUIRED = 'subscribe.confirm_required';
    const SUBSCRIBE_ALREADY_EXISTS   = 'subscribe.already_exists';
    const VERIFY_SUCCESS             = 'verify.success';
    const UNSUBSCRIBE_SUCCESS        = 'unsubscribe.success';
    const UNSUBSCRIBE_ALL_SUCCESS    = 'unsubscribe_all.success';
    const DOWNLOAD_FORMAT_MISSING    = 'download.format_missing';
    const DOWNLOAD_FORMAT_UNKNOWN    = 'download.format_unknown';
    const DOWNLOAD_FAILED            = 'download.failed';

    /**
     * @return array key => [ level, text (with :placeholders), shown when, shown where ]
     */
    public static function catalog(): array
    {
        $subscribeBox = 'Subscribe-Box auf /aufträge, direkt unter dem Formular (JS, JSON-Antwort von POST /subscribe)';
        $noJsPage     = 'Ergebnisseite (public/subscriptions/result), wenn das Formular ohne JavaScript abgeschickt wird';

        return [
            self::SUBSCRIBE_CREATED => [self::LEVEL_SUCCESS,
                'Benachrichtigung wurde erstellt. Um die Benachrichtigung zu aktivieren, klicken Sie auf den Bestätigungs-Link den Sie in Kürze von uns per E-Mail erhalten. Sollten Sie keine E-Mail erhalten haben überprüfen Sie bitte Ihren Spam-Ordner.',
                'Abo wurde gespeichert und die Bestätigungs-E-Mail verschickt (HTTP 200).',
                $subscribeBox . '; ' . $noJsPage],
            self::SUBSCRIBE_FAILED => [self::LEVEL_ERROR,
                'Beim Erstellen der Benachrichtigung ist ein Fehler aufgetreten.',
                'Unerwartete Exception beim Speichern oder Senden, nur in production (HTTP 500); im Browser auch, wenn die Anfrage das Netzwerk nicht erreicht.',
                $subscribeBox . '; ' . $noJsPage],
            self::SUBSCRIBE_INVALID_QUERY => [self::LEVEL_ERROR,
                'Fehler bei Filterabfrage.',
                'Das Feld "query" entspricht nicht den angewendeten Filtern, z.B. manipuliertes Formular (HTTP 422).',
                $subscribeBox . '; ' . $noJsPage],
            self::SUBSCRIBE_CONFIRM_REQUIRED => [self::LEVEL_ERROR,
                'Die Bedingungen zum Datenschutz müssen gelesen und akzeptiert werden.',
                'Datenschutz-Checkbox nicht angehakt (HTTP 422, Feldfehler "confirm").',
                'Subscribe-Box auf /aufträge: rot markierte Checkbox und Fehlerliste über dem Formular; ' . $noJsPage],
            self::SUBSCRIBE_ALREADY_EXISTS => [self::LEVEL_ERROR,
                'Die Benachrichtigung zu dieser Abfrage ist bereits eingerichtet.',
                'Diese E-Mail-Adresse hat dieselbe Filterabfrage schon abonniert (HTTP 422, Feldfehler "query").',
                'Subscribe-Box auf /aufträge: Fehlerliste über dem Formular; ' . $noJsPage],
            self::VERIFY_SUCCESS => [self::LEVEL_SUCCESS,
                'Benachrichtigung :title wurde bestätigt.',
                'Klick auf den Bestätigungs-Link in der Verifizierungs-E-Mail (GET /subscriptions/{id}/verify/{email}).',
                'Ergebnisseite (public/subscriptions/result) mit Link zurück zu /aufträge'],
            self::UNSUBSCRIBE_SUCCESS => [self::LEVEL_SUCCESS,
                'Benachrichtigung :title beendet.',
                'Klick auf "Abo beenden" auf der Bestätigungsseite (GET /subscriptions/{id}/unsubscribe/{email}).',
                'Ergebnisseite (public/subscriptions/result) mit Link zurück zu /aufträge'],
            self::UNSUBSCRIBE_ALL_SUCCESS => [self::LEVEL_SUCCESS,
                'Alle Benachrichtigungen wurden beendet, Sie werden von uns keine weiteren E-Mail Benachrichtigungen erhalten.',
                'Klick auf "Alle Abos beenden" auf der Bestätigungsseite (GET /subscriptions/all/unsubscribe/{email}).',
                'Ergebnisseite (public/subscriptions/result) mit Link zurück zu /aufträge'],
            self::DOWNLOAD_FORMAT_MISSING => [self::LEVEL_ERROR,
                'Download Format nicht angegeben.',
                'GET /downloads/kerndaten_dump_daily ohne ?format= (HTTP 422).',
                'Fehlerseite (public/downloads/error) mit Link zurück zu /downloads'],
            self::DOWNLOAD_FORMAT_UNKNOWN => [self::LEVEL_ERROR,
                'Unbekanntes Format angegeben.',
                'GET /downloads/kerndaten_dump_daily?format=... mit anderem Format als csv (HTTP 422).',
                'Fehlerseite (public/downloads/error) mit Link zurück zu /downloads'],
            self::DOWNLOAD_FAILED => [self::LEVEL_ERROR,
                'Beim Erstellen des Downloads ist ein Fehler aufgetreten.',
                'Der temporäre Download-Link (Symlink in public/tmp) konnte nicht angelegt werden (HTTP 500).',
                'Fehlerseite (public/downloads/error) mit Link zurück zu /downloads'],
        ];
    }

    /**
     * @param string $key  one of the constants
     * @param array  $replace placeholders, e.g. ['title' => 'Mein Abo']
     */
    public static function text(string $key, array $replace = []): string
    {
        $text = self::catalog()[$key][1];

        foreach ($replace as $name => $value) {
            $text = str_replace(':' . $name, $value, $text);
        }

        return $text;
    }
}
