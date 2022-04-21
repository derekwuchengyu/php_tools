<?
namespace Think\Selflib;
use Think\Controller;
use \GuzzleHttp\Client as CurlClient;
use DateTime;


class GoogleCalendar extends Controller 
{
	private static $keyPath;
    private static $calendarId;

    public function __construct(){
        self::$keyPath = __ROOT__ .'Public/ApiKey/googleCalendar/';
        self::$calendarId  = 'addcn.com_ieb2ku4nt0a3mf49s75qukjbck@group.calendar.google.com';
    }

    /**
     * Returns an authorized API client.
     * @return Google_Client the authorized client object
     */
    private function getClient()
    {
        require __ROOT__ . 'vendor/autoload.php';

        $client = new \Google_Client();
        $client->setApplicationName('Google Calendar API PHP');
        $client->setScopes(\Google_Service_Calendar::CALENDAR_EVENTS);
        $client->setAuthConfig(self::$keyPath.'client_secret.json');
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');



        // Load previously authorized token from a file, if it exists.
        // The file token.json stores the user's access and refresh tokens, and is
        // created automatically when the authorization flow completes for the first
        // time.
        $tokenPath = self::$keyPath.'token.json';
        if (file_exists($tokenPath)) {
            $accessToken = json_decode(file_get_contents($tokenPath), true);
            $client->setAccessToken($accessToken);
        }

        // If there is no previous token or it's expired.
        if ($client->isAccessTokenExpired()) {
            // Refresh the token if possible, else fetch a new one.
            if ($client->getRefreshToken()) {
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            } else {
                echo "Google Token Invalid.";
                $this->error('Google Token須更新, 請洽技術部。');
                exit;
            }
            // Save the token to a file.
            if (!file_exists(dirname($tokenPath))) {
                mkdir(dirname($tokenPath), 0700, true);
            }
            file_put_contents($tokenPath, json_encode($client->getAccessToken()));
        }

        return $client;
    }

    /**列出未來活動**/
    function listEvent()
    {
        $client = $this->getClient();
        $service = new \Google_Service_Calendar($client);

        // Print the next 100 events on the user's calendar.
        $optParams = array(
          'maxResults' => 10,
          'orderBy' => 'startTime',
          'singleEvents' => true,
          'timeMin' => date('c'),
        );
        $results = $service->events->listEvents(self::$calendarId, $optParams);
        $events = $results->getItems();
        // echo"<PRE>";print_r($events);exit;

        if (empty($events)) {
            return "No upcoming events found.\n";
        } else {
            return $events;
        }
    }

    /**建立活動**/
    function insertEvent($startTime, $endTime, $Title='', $description='', $location='', $attendees=[], $reminders=[], $recurrence=[])
    {
        $client = $this->getClient();
        $service = new \Google_Service_Calendar($client);


        $objDateTime = new \DateTime($startTime);
        $startDate = $objDateTime->format(DateTime::ISO8601);
        $objDateTime = new \DateTime($endTime);
        $endDate = $objDateTime->format(DateTime::ISO8601);

        $event = new \Google_Service_Calendar_Event(array(
          //標題
          'summary' => $Title,
          'location' => $location,
          //說明
          'description' => $description,
          'start' => array(
            'dateTime' => $startDate,
            'timeZone' => 'Asia/Taipei',
          ),
          'end' => array(
            'dateTime' => $endDate,
            'timeZone' => 'Asia/Taipei',
          ),
          //重複
          'recurrence' => $recurrence,
          //邀請對象
          'attendees' => $attendees,
          //通知
          'reminders' => $reminders,
        ));

        $event = $service->events->insert(self::$calendarId, $event);
        return $event->id;
        // printf('Event created: %s\n', $event->htmlLink);
    }

    /**刪除活動**/
    function deleteEvent($eventId)
    {
        $client = $this->getClient();
        $service = new \Google_Service_Calendar($client);
        $service->events->delete(self::$calendarId, $eventId);
    }
}

/** 透過xshell 執行更新token 
 ** docker exec -it php7 cd /home/htdocs/simplework/ && php /home/htdocs/simplework/ThinkPHP/Library/Think/Selflib/GoogleCalendar.class.php
 **/
function getClientInCommand()
{
    echo"Checking token....\n";
    require '/home/htdocs/simplework/vendor/autoload.php';
    
    $client = new \Google_Client();
    
    $client->setApplicationName('Google Calendar API PHP Quickstart');
    $client->setScopes(\Google_Service_Calendar::CALENDAR_EVENTS);
    $client->setAuthConfig('/home/htdocs/simplework/Public/ApiKey/googleCalendar/client_secret.json');
    $client->setAccessType('offline');
    $client->setPrompt('select_account consent');

    $client->useApplicationDefaultCredentials();
    $client->addScope(\Google_Service_Drive::DRIVE);
    $client->setSubject($user_to_impersonate);

    // Load previously authorized token from a file, if it exists.
    // The file token.json stores the user's access and refresh tokens, and is
    // created automatically when the authorization flow completes for the first
    // time.
    $tokenPath = '/home/htdocs/simplework/Public/ApiKey/googleCalendar/token.json';
    if (file_exists($tokenPath)) {
        $accessToken = json_decode(file_get_contents($tokenPath), true);
        $client->setAccessToken($accessToken);
    }

    // If there is no previous token or it's expired.
    if ($client->isAccessTokenExpired()) {
        // Refresh the token if possible, else fetch a new one.
        if ($client->getRefreshToken()) {
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
        } else {
            // Request authorization from the user.
            $authUrl = $client->createAuthUrl();
            printf("Open the following link in your browser:\n%s\n", $authUrl);
            print 'Enter verification code: ';
            $authCode = trim(fgets(STDIN));

            // Exchange authorization code for an access token.
            $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
            $client->setAccessToken($accessToken);

            // Check to see if there was an error.
            if (array_key_exists('error', $accessToken)) {
                throw new Exception(join(', ', $accessToken));
            }
        }
        // Save the token to a file.
        if (!file_exists(dirname($tokenPath))) {
            mkdir(dirname($tokenPath), 0700, true);
        }
        file_put_contents($tokenPath, json_encode($client->getAccessToken()));
    }
    return $client;
}

if (php_sapi_name() == 'cli') {
    getClientInCommand();
}




?>
