<?php

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\Setting;
use App\Models\Device;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Stichoza\GoogleTranslate\GoogleTranslate;


function mail_footer($type)
{
    return [
        'notification_type' => $type,
        'logged_in_user_fullname' => auth()->user() ? auth()->user()->full_name ?? default_user_name() : '',
        'logged_in_user_role' => auth()->user() ? auth()->user()->getRoleNames()->first()->name ?? '-' : '',
        'company_name' => setting('app_name'),
        'company_contact_info' => implode('', [
            setting('helpline_number') . PHP_EOL,
            setting('inquriy_email'),
        ]),
    ];
}


function sendNotification($data)
{
    $mailable = \Modules\NotificationTemplate\Models\NotificationTemplate::where('type', $data['notification_type'])->with('defaultNotificationTemplateMap')->first();
    if ($mailable != null && $mailable->to != null) {
        $mails = json_decode($mailable->to);

        foreach ($mails as $key => $mailTo) {
            $data['type'] = $data['notification_type'];
            $subscription = isset($data['subscription']) ? $data['subscription'] : null;
            if (isset($subscription) && $subscription != null) {
                $data['id'] = $subscription['id'];
                $data['user_id'] = $subscription['user_id'];
                $data['plan_id'] = $subscription['plan_id'];
                $data['name'] = $subscription['name'];
                $data['identifier'] = $subscription['identifier'];
                $data['type'] = $subscription['type'];
                $data['status'] = $subscription['status'];
                $data['amount'] = $subscription['amount'];
                $data['plan_type'] = $subscription['plan_type'];
                $data['username'] = $subscription['user']->full_name;
                $data['notification_group'] = 'subscription';
                $data['site_url'] = env('APP_URL');

                unset($data['subscription']);

            }

            switch ($mailTo) {
                case 'admin':

                    $admin = \App\Models\User::role('admin')->first();

                    if (isset($admin->email)) {
                        try {
                            $admin->notify(new \App\Notifications\CommonNotification($data['notification_type'], $data));
                        } catch (\Exception $e) {
                            Log::error($e);
                        }
                    }

                    break;
                case 'demo_admin':

                    $demoadmin = \App\Models\User::role('demo_Admin')->first();

                    if (isset($demoadmin->email)) {
                        try {
                            $demoadmin->notify(new \App\Notifications\CommonNotification($data['notification_type'], $data));
                        } catch (\Exception $e) {
                            Log::error($e);
                        }
                    }

                    break;
                case 'user':
                    if (isset($data['user_id'])) {
                        $user = \App\Models\User::find($data['user_id']);
                        try {
                            $user->notify(new \App\Notifications\CommonNotification($data['notification_type'], $data));
                        } catch (\Exception $e) {
                            Log::error($e);
                        }
                    }

                    break;
            }
        }
    }
}
function sendNotifications($data)
{

    $heading = '#' . $data['id'] . ' ' . str_replace("_", " ", $data['name']);
    $content = strip_tags($data['description']);
    $appName = env('APP_NAME');
    $topic = str_replace(' ', '_', strtolower($appName));
    $type = $data['type'];
    $additionalData = json_encode($data);
    return fcm([

        "message" => [
            "topic" => $topic,
            "notification" => [
                "title" => $heading,
                "body" => $content,
            ],
            "data" => [
                "sound" => "default",
                "story_id" => "story_12345",
                "type" => $type,
                "additional_data" => $additionalData,
                "click_action" => "FLUTTER_NOTIFICATION_CLICK",
            ],
            "android" => [
                "priority" => "high",
                "notification" => [
                    "click_action" => "FLUTTER_NOTIFICATION_CLICK",
                ],
            ],
            "apns" => [
                "payload" => [
                    "aps" => [
                        "category" => $type,
                    ],
                ],
            ],
        ],

    ]);

}
function fcm($fields)
{

    $otherSetting = \App\Models\Setting::where('type', 'appconfig')->where('name', 'firebase_key')->first();


    $projectID = $otherSetting->val ?? null;

    Log::info($projectID);

    $access_token = getAccessToken();

    $headers = [
        'Authorization: Bearer ' . $access_token,
        'Content-Type: application/json',
    ];
    $ch = curl_init('https://fcm.googleapis.com/v1/projects/' . $projectID . '/messages:send');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));

    $response = curl_exec($ch);
    Log::info($response);
    curl_close($ch);
}
function getAccessToken()
{
    $directory = storage_path('app/data');
    $credentialsFiles = File::glob($directory . '/*.json');

    if (empty($credentialsFiles)) {
        throw new Exception('No JSON credentials found in the specified directory.');
    }
    $client = new Google_Client();
    $client->setAuthConfig($credentialsFiles[0]);
    $client->addScope('https://www.googleapis.com/auth/firebase.messaging');

    $token = $client->fetchAccessTokenWithAssertion();

    return $token['access_token'];
}

function formatOffset($offset)
{
    $hours = $offset / 3600;
    $remainder = $offset % 3600;
    $sign = $hours > 0 ? '+' : '-';
    $hour = (int) abs($hours);
    $minutes = (int) abs($remainder / 60);

    if ($hour == 0 and $minutes == 0) {
        $sign = ' ';
    }

    return 'GMT' . $sign . str_pad($hour, 2, '0', STR_PAD_LEFT)
        . ':' . str_pad($minutes, 2, '0');
}

function timeZoneList()
{
    $list = \DateTimeZone::listAbbreviations();
    $idents = \DateTimeZone::listIdentifiers();

    $data = $offset = $added = [];
    foreach ($list as $abbr => $info) {
        foreach ($info as $zone) {
            if (!empty($zone['timezone_id']) and !in_array($zone['timezone_id'], $added) and in_array($zone['timezone_id'], $idents)) {
                $z = new \DateTimeZone($zone['timezone_id']);
                $c = new \DateTime(null, $z);
                $zone['time'] = $c->format('H:i a');
                $offset[] = $zone['offset'] = $z->getOffset($c);
                $data[] = $zone;
                $added[] = $zone['timezone_id'];
            }
        }
    }

    array_multisort($offset, SORT_ASC, $data);
    $options = [];
    foreach ($data as $key => $row) {
        $options[$row['timezone_id']] = $row['time'] . ' - ' . formatOffset($row['offset']) . ' ' . $row['timezone_id'];
    }

    return $options;
}

/*
 * Global helpers file with misc functions.
 */
if (!function_exists('app_name')) {
    /**
     * Helper to grab the application name.
     *
     * @return mixed
     */
    function app_name()
    {
        return setting('app_name') ?? config('app.name');
    }
}
/**
 * Avatar Find By Gender
 */
if (!function_exists('default_user_avatar')) {
    function default_user_avatar()
    {
        return asset(config('app.avatar_base_path') . 'avatar.webp');
    }
    function default_user_name()
    {
        return __('messages.unknown_user');
    }
}
if (!function_exists('user_avatar')) {
    function user_avatar()
    {
        if (auth()->user()->file_url ?? null) {
            return auth()->user()->file_url;
        } else {
            return asset(config('app.avatar_base_path') . 'avatar.webp');
        }
    }
}

if (!function_exists('default_file_url')) {
    function default_file_url()
    {
        return asset(config('app.image_path') . 'default.webp');
    }
}

/*
 * Global helpers file with misc functions.
 */
if (!function_exists('user_registration')) {
    /**
     * Helper to grab the application name.
     *
     * @return mixed
     */
    function user_registration()
    {
        $user_registration = false;

        if (env('USER_REGISTRATION') == 'true') {
            $user_registration = true;
        }

        return $user_registration;
    }
}

/**
 * Global Json DD
 * !USAGE
 * return jdd($id);
 */
if (!function_exists('jdd')) {
    function jdd($data)
    {
        return response()->json($data, 500);
        exit();
    }
}

/*
 *
 * label_case
 *
 * ------------------------------------------------------------------------
 */
if (!function_exists('label_case')) {
    /**
     * Prepare the Column Name for Lables.
     */
    function label_case($text)
    {
        $order = ['_', '-'];
        $replace = ' ';

        $new_text = trim(\Illuminate\Support\Str::title(str_replace('"', '', $text)));
        $new_text = trim(\Illuminate\Support\Str::title(str_replace($order, $replace, $text)));
        $new_text = preg_replace('!\s+!', ' ', $new_text);

        return $new_text;
    }
}


if (!function_exists('fielf_required')) {
    /**
     * Prepare the Column Name for Lables.
     */
    function fielf_required($required)
    {
        $return_text = '';

        if ($required != '') {
            $return_text = '<span class="text-danger">*</span>';
        }

        return $return_text;
    }
}

/*
 * Get or Set the Settings Values
 *
 * @var [type]
 */
if (!function_exists('setting')) {
    function setting($key, $default = null)
    {
        if (is_null($key)) {
            return new App\Models\Setting();
        }

        if (is_array($key)) {
            return App\Models\Setting::set($key[0], $key[1]);
        }

        $value = App\Models\Setting::get($key);
        return is_null($value) ? value($default) : $value;
    }
}

/*
 * Show Human readable file size
 *
 * @var [type]
 */
if (!function_exists('humanFilesize')) {
    function humanFilesize($size, $precision = 2)
    {
        $units = ['B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        $step = 1024;
        $i = 0;

        while (($size / $step) > 0.9) {
            $size = $size / $step;
            $i++;
        }

        return round($size, $precision) . $units[$i];
    }
}



/*
 *
 * Prepare a Slug for a given string
 * Laravel default str_slug does not work for Unicode
 *
 * ------------------------------------------------------------------------
 */
if (!function_exists('slug_format')) {
    /**
     * Format a string to Slug.
     */
    function slug_format($string)
    {
        $base_string = $string;

        $string = preg_replace('/\s+/u', '-', trim($string));
        $string = str_replace('/', '-', $string);
        $string = str_replace('\\', '-', $string);
        $string = strtolower($string);

        $slug_string = $string;

        return $slug_string;
    }
}

/*
 *
 * icon
 * A short and easy way to show icon fornts
 * Default value will be check icon from FontAwesome
 *
 * ------------------------------------------------------------------------
 */
if (!function_exists('icon')) {
    /**
     * Format a string to Slug.
     */
    function icon($string = 'fas fa-check')
    {
        $return_string = "<i class='" . $string . "'></i>";

        return $return_string;
    }
}



if (!function_exists('language_direction')) {
    /**
     * return direction of languages.
     *
     * @return string
     */
    function language_direction($language = null)
    {
        if (empty($language)) {
            $language = app()->getLocale();
        }
        $language = strtolower(substr($language, 0, 2));
        $rtlLanguages = [
            'ar', //  'العربية', Arabic
            'arc', //  'ܐܪܡܝܐ', Aramaic
            'bcc', //  'بلوچی مکرانی', Southern Balochi
            'bqi', //  'بختياري', Bakthiari
            'ckb', //  'Soranî / کوردی', Sorani Kurdish
            'dv', //  'ދިވެހިބަސް', Dhivehi
            'fa', //  'فارسی', Persian
            'glk', //  'گیلکی', Gilaki
            'he', //  'עברית', Hebrew
            'lrc', //- 'لوری', Northern Luri
            'mzn', //  'مازِرونی', Mazanderani
            'pnb', //  'پنجابی', Western Punjabi
            'ps', //  'پښتو', Pashto
            'sd', //  'سنڌي', Sindhi
            'ug', //  'Uyghurche / ئۇيغۇرچە', Uyghur
            'ur', //  'اردو', Urdu
            'yi', //  'ייִדיש', Yiddish
        ];
        if (in_array($language, $rtlLanguages)) {
            return 'rtl';
        }

        return 'ltr';
    }
}




function getCustomizationSetting($name, $key = 'customization_json')
{
    $settingObject = setting($key);
    if (isset($settingObject) && $key == 'customization_json') {
        try {
            $settings = (array) json_decode(html_entity_decode(stripslashes($settingObject)))->setting;

            return collect($settings[$name])['value'];
        } catch (\Exception $e) {
            return '';
        }

        return '';
    } elseif ($key == 'root_color') {
        //
    }

    return '';
}


function str_slug($title, $separator = '-', $language = 'en')
{
    return Str::slug($title, $separator, $language);
}
function formatDuration($duration)
{
    if (strpos($duration, ':') !== false) {
        list($hours, $minutes) = explode(':', $duration);
        $hours = intval($hours);
        $minutes = str_pad(intval($minutes), 2, '0', STR_PAD_LEFT);
        return "{$hours} hrs {$minutes} min";
    }

    return $duration;
}

function formatCurrency($number, $noOfDecimal, $decimalSeparator, $thousandSeparator, $currencyPosition, $currencySymbol)
{

    $formattedNumber = number_format($number, $noOfDecimal, '.', '');


    $parts = explode('.', $formattedNumber);
    $integerPart = $parts[0];
    $decimalPart = isset($parts[1]) ? $parts[1] : '';

    $integerPart = number_format($integerPart, 0, '', $thousandSeparator);


    $currencyString = '';

    if ($currencyPosition == 'left' || $currencyPosition == 'left_with_space') {
        $currencyString .= $currencySymbol;
        if ($currencyPosition == 'left_with_space') {
            $currencyString .= ' ';
        }

        $currencyString .= $integerPart;

        if ($noOfDecimal > 0) {
            $currencyString .= $decimalSeparator . $decimalPart;
        }
    }


    if ($currencyPosition == 'right' || $currencyPosition == 'right_with_space') {

        if ($noOfDecimal > 0) {
            $currencyString .= $integerPart . $decimalSeparator . $decimalPart;
        }
        if ($currencyPosition == 'right_with_space') {
            $currencyString .= ' ';
        }
        $currencyString .= $currencySymbol;
    }

    return $currencyString;
}


function formatUpdatedAt($updatedAt)
{
    $diff = Carbon::now()->diffInHours($updatedAt);
    return $diff < 25 ? $updatedAt->diffForHumans() : $updatedAt->isoFormat('llll');
}
function storeMediaFileAWS($module, $filePath, $key = 'file_url')
{
    // Clear existing media collection
    $module->clearMediaCollection($key);

    // Store the file using Laravel's media library
    $mediaItems = $module->addMedia($filePath)->toMediaCollection($key);

    // Get the stored file's path or name
    if ($mediaItems->count() > 0) {
        // Return the path or name of the stored file
        return $mediaItems[0]->file_name; // Adjust this based on your media library configuration
    }

    return null; // Return null or handle error as needed
}

function storeMediaFile($module, $files, $key = 'file_url')
{

    $module->clearMediaCollection($key);

    if (is_array($files)) {
        foreach ($files as $file) {
            if (!empty($file)) {
                $module->addMedia($file)->toMediaCollection($key);
            }
        }
    } else {
        $module->clearMediaCollection($key);
        $mediaItems = $module->addMedia($files)->toMediaCollection($key);
    }
}




function getMediaUrls($searchQuery = null, $perPage = 21, $page = 1)
{
    $activeDisk = DB::table('settings')->where('name', 'disc_type')->value('val') ?? env('ACTIVE_STORAGE','local');
   // $activeDisk = env('ACTIVE_STORAGE'); // set on live server

    $folder = $activeDisk === 'local' ? 'public/streamit-laravel' : 'streamit-laravel';
    $files = Storage::disk($activeDisk)->files($folder);

    // Get file creation timestamps and sort in descending order
    $filesWithTimestamps = array_map(function ($file) use ($activeDisk) {
        return [
            'file' => $file,
            'timestamp' => Storage::disk($activeDisk)->lastModified($file),
        ];
    }, $files);

    // Sort by timestamp in descending order
    usort($filesWithTimestamps, function ($a, $b) {
        return $b['timestamp'] <=> $a['timestamp'];
    });

    // Extract the sorted file list
    $files = array_column($filesWithTimestamps, 'file');


    if ($searchQuery) {
        $files = array_filter($files, function ($file) use ($searchQuery) {
            // Ensure $file is a string and check if it contains the search query
            return is_string($file) && stripos($file, $searchQuery) !== false;
        });
    }

    $totalFiles = count($files);
    $offset = ($page - 1) * $perPage;
    $paginatedFiles = array_slice($files, $offset, $perPage);

    $mediaUrls = array_map(function ($file) use ($activeDisk) {
        if ($activeDisk === 'local') {
            $file = str_replace('public/', '', $file);
            return asset('storage/' . $file);
        } else {
            return Storage::disk($activeDisk)->url($file);
        }
    }, $paginatedFiles);

    return [
        'mediaUrls' => $mediaUrls,
        'hasMore' => $offset + $perPage < $totalFiles,
    ];
}


    if (!function_exists('setDefaultImage')) {
        function setDefaultImage($fileUrl = '')
        {
            $defaultImagePath = '/default-image/Default-Image.jpg';
            $defaultImage = asset($defaultImagePath);

            if (empty($fileUrl)) {
                return $defaultImage;
            }

            return $fileUrl;
        }
    }



if (!function_exists('getImageUrlOrDefault')) {
    /**
     * Check if the image exists, return the file URL or the default image URL.
     *
     * @param string $fileUrl The full URL of the file to check
     * @return string The valid file URL or the default image URL
     */
    function getImageUrlOrDefault($fileUrl)
    {

        $fileUrl = setBaseUrlWithFileName($fileUrl);

        return $fileUrl;

    }
}


function formatDate($date)
{

    $releaseDate = Carbon::createFromFormat('Y-m-d', $date);
    $formattedDate = $releaseDate->format('jS F Y');
    return $formattedDate;
}

function isenablemodule($key)
{
    $setting = Setting::where('name', $key)->value('val');
    return $setting !== null ? $setting : 0;
}

function gettmdbapiKey()
{
    $tbdb_key = Setting::where('name', 'tmdb_api_key')->value('val');
    return $tbdb_key !== null ? $tbdb_key : null;
}

function getCurrentProfile($user_id, $request)
{
    $device_id = $request->ip();

    return Device::where('user_id', $user_id)
        ->where('device_id', $device_id)
        ->value('active_profile');
}



function isSmtpConfigured()
{
    $host = config('mail.mailers.smtp.host');
    $port = config('mail.mailers.smtp.port');
    $username = config('mail.mailers.smtp.username');
    $password = config('mail.mailers.smtp.password');

    return !empty($host) &&
        !empty($port) &&
        !empty($username) &&
        !empty($password) &&
        $username !== 'null' &&
        $password !== 'null';
}

function decryptVideoUrl($encryptedUrl)
{


    try {
        // Decrypt the URL
        $decryptedUrl = Crypt::decryptString(urldecode($encryptedUrl));

        // Check if the URL is a YouTube link
        preg_match("/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^\"&?\/ ]{11})/", $decryptedUrl, $youtubeMatches);
        if (isset($youtubeMatches[1])) {
            return ['platform' => 'youtube', 'videoId' => $youtubeMatches[1]];
        }

        // Check if the URL is a Vimeo link
        preg_match("/player\.vimeo\.com\/video\/(\d+)/", $decryptedUrl, $vimeoMatches);
        if (isset($vimeoMatches[1])) {
            return ['platform' => 'vimeo', 'videoId' => $vimeoMatches[1]];
        }

        // Check if the URL is an HLS stream (m3u8)
        if (preg_match('/\.m3u8$/', $decryptedUrl)) {
            return ['platform' => 'hls', 'url' => $decryptedUrl];
        }

        // Check if it's a local file
        $filePath = str_replace(url('/storage'), 'public', $decryptedUrl);
        if (Storage::exists($filePath)) {
            $actualPath = Storage::path($filePath);
            $fileMimeType = mime_content_type($actualPath);
            return ['platform' => 'local', 'url' => $actualPath, 'mimeType' => $fileMimeType];
        }

        // If file not found

        return ['error' => 'File not found'];
    } catch (\Exception $e) {

        return ['error' => 'Invalid encrypted URL'];
    }
}

function extractFileNameFromUrl($url = '')
{
    return basename(parse_url($url, PHP_URL_PATH));
}


// function setBaseUrlWithFileName($url = '')
// {


//     if (empty($url)) {
//         return setDefaultImage();
//     }

//     $isRemote = filter_var($url, FILTER_VALIDATE_URL) !== false;

//     if($isRemote){

//         if (checkImageExists($url)) {
//             return $url;
//         }
//     } else {

//         $fileName = basename($url);
//         $activeDisk = env('ACTIVE_STORAGE', 'local');

//         if ($activeDisk === 'local') {
//             $filePath = public_path("storage/streamit-laravel/$fileName");
//             if (file_exists($filePath)) {
//                 return asset("storage/streamit-laravel/$fileName");
//             }
//         } else {

//             $baseUrl = rtrim(env('DO_SPACES_URL'), '/');
//             $filePath = "$baseUrl/streamit-laravel/$fileName";

//             if (checkImageExists($filePath)) {
//                 return $filePath;
//             }
//         }
//     }
//     return setDefaultImage();
// }

function setBaseUrlWithFileName($url = '')
{
    // Return a default image if the URL is empty
    if (empty($url)) {
        return setDefaultImage();
    }

    // Check if the URL is remote
    $isRemote = filter_var($url, FILTER_VALIDATE_URL) !== false;

    // Handle remote URL
    if ($isRemote) {
        // Return immediately if the remote image exists
        return $url;

       return checkImageExists($url) ? $url : setDefaultImage();
    }

    // Extract the file name
    $fileName = basename($url);
    $activeDisk = env('ACTIVE_STORAGE', 'local');

    // Handle local storage
    if ($activeDisk === 'local') {
        $filePath = public_path("storage/streamit-laravel/$fileName");

        // Return local asset path if the file exists
        if (file_exists($filePath)) {
            return asset("storage/streamit-laravel/$fileName");
        }
    } else {
        // Handle remote storage
        $baseUrl = rtrim(env('DO_SPACES_URL'), '/');
        $filePath = "$baseUrl/streamit-laravel/$fileName";

        // Return remote file URL if it exists
        if (checkImageExists($filePath)) {
            return $filePath;
        }
    }

    // Return a default image as fallback
    return setDefaultImage();
}


function checkImageExists($url)
{
    $headers = @get_headers($url);

    if ($headers && strpos($headers[0], '200') !== false) {
        return true;
    } else {
        return false;
    }
}


function getIdsBySlug($slug)
{
    return json_decode(App\Models\MobileSetting::getValueBySlug($slug));
}

function getResourceCollection($model, $ids, $resource, $toArray = false)
{

    if (empty($ids) || !is_array($ids)) {
        return $toArray ? [] : collect();
    }
    $query = $model::whereIn('id', $ids);

    if (\Schema::hasColumn((new $model)->getTable(), 'status')) {
        $query->where('status', 1);
    }

    $items = $query->get();


    $collection = $resource::collection($items);

    return $toArray ? $collection->toArray(request()) : $collection;
}

function setavatarBaseUrl($url = '')
{

    if ($url != '') {

        $baseUrl =  url('/');

        return $baseUrl . $url;

    } else {

        return setDefaultImage();
    }
}

function translate($text)
{

    $currentLang = app()->getLocale();
    return GoogleTranslate::trans($text, $currentLang);
}


if (!function_exists('isActive')) {
    /**
     * Returns 'active' or 'done' class based on the current step.
     *
     * @param  string|array  $route
     * @param  string  $className
     * @return string
     */
    function isActive($route, $className = 'active') {
        $currentRoute = Route::currentRouteName();

        if (is_array($route)) {
            return in_array($currentRoute, $route) ? $className : '';
        }

        return $currentRoute == $route ? $className : '';
    }
}

function dbConnectionStatus(): bool
{
    try {
        DB::connection()->getPdo();
    return true;
    } catch (Exception $e) {
        return false;
    }
}

if (!function_exists('getFooterData')) {
    function getFooterData()
    {
        $cacheKey = 'footer_data';
        $data = Cache::get($cacheKey);
           if(!$data){

                if (function_exists('isenablemodule') && isenablemodule('tvshow') == 1) {
                    $data['premiumShows'] = \Modules\Entertainment\Models\Entertainment::where('movie_access', 'paid')
                        ->take(4)
                        ->get();
                }

                if (function_exists('isenablemodule') && isenablemodule('movie') == 1) {
                    $data['topMovies'] = \Modules\Entertainment\Models\Entertainment::where('type', 'movie')
                        ->where('IMDb_rating', '>', 5)
                        ->orderBy('IMDb_rating', 'desc')
                        ->take(4)
                        ->get();
                }
                $data['pages'] = \Modules\Page\Models\Page::where('status', 1)->get();
                Cache::put($cacheKey, $data);
           }
        return $data;
        }
    }


