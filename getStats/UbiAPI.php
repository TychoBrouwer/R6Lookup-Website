<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);

class UbiAPI
{
    private $b64authcreds;
    public $http_useragent='Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.87 Safari/537.36';
    private $defaultHeaders = [
        'Accept: */*',
        'Accept-Encoding: gzip, deflate, br',
        'Accept-Language: en-GB,en;q=0.5',
        'Connection: keep-alive',
        'Origin: https://www.ubisoft.com',
        'Ubi-AppId: 3587dcbb-7f81-457c-9781-0e3f29f6f56a',
        'Ubi-SessionId: 22060f4d-0e94-4bea-aec8-68f807bc5f64',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:83.0) Gecko/20100101 Firefox/83.0',
        'Host: public-ubiservices.ubi.com',
        'Cache-Control: no-cache',
    ];
    private $seasonsS = '-1,-2,-3,-4,-5,-6,-7,-8,-9,-10,-11,-12,-13,-14,-15,-16,-17,-18,-19';

    public function __construct($email, $password)
    {
        $this->b64authcreds=$this->generateB64Creds($email.':'.$password);
    }

    public function generateB64Creds($emailandpassword)
    {
        return base64_encode($emailandpassword);
    }

    public function login()
    {
        $request_url = 'https://public-ubiservices.ubi.com/v3/profiles/sessions';

        $addHeaders = [
            'Content-Type: application/json; charset=utf-8',
            'Authorization: Basic ' . $this->b64authcreds,
            'X-Requested-With: XMLHttpRequest',
            'Referer: https://public-ubiservices.ubi.com/Default/Login?appId=3587dcbb-7f81-457c-9781-0e3f29f6f56a&lang=en-US&nextUrl=https%3A%2F%2Fclub.ubisoft.com%2Flogged-in.html%3Flocale%3Den-US',
            'Content-Lenght: 19',
            'expiration: null',
        ];

        $headers = array_merge(
            $this->defaultHeaders,
            $addHeaders
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $request_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, '{\'rememberMe\':true}');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $ubioutput = curl_exec($ch);
        $orginaloutput = $ubioutput;

        curl_close($ch);
        $test_beforeSave = $this->saveTicket(false);
        $this->saveTicket(true, $ubioutput);
        $test_afterSave = $this->saveTicket(false);
        $test_fileUpdated = false;

        if ($test_beforeSave != $test_afterSave) {
            $test_fileUpdated = true;
        }

        return array(
            'error' => false,
            'content' => 'Ticket Updated? (1==true):'.$test_fileUpdated,
            'b64authcreds' => $this->b64authcreds,
            'ubioutput' => $ubioutput
        );
    }

    public function uplayticket($check = true)
    {
        $ticket = json_decode($this->saveTicket(false), true);
        if ((!isset($ticket['expiration']) || isset($ticket['error']) && $ticket['error'] == true || isset($ticket['errorCode'])) && $check) {
            $this->login();
            return $this->uplayticket(false);
        } elseif ($check) {
            $time = strtotime($ticket['expiration']);
            if ($time < time()) {
                $this->login();
                return $this->uplayticket(false);
            }
        }
        if (!isset($ticket['ticket'])) {
            return '';
        }
        $ticket = $ticket['ticket'];

        $prefix = 'Ubi_v1 t=';
        return $prefix.$ticket;
    }

    private function saveTicket($save, $ticket = '')
    {
        $url = $_SERVER['DOCUMENT_ROOT'] . "/getStats/api_ticket";

        if ($save) {
            $file_ticket = fopen($url, 'w') or die('Can\'t open ticket file');

            try {
                fwrite($file_ticket, $ticket);
                return true;
            } catch (Exception $e) {
                return false;
            }
        } else {
            $ticket_file = fopen($url, 'r') or die('{error:true}');
            $ticket = fgets($ticket_file);
            return $ticket;
        }
    }

///////////////////////////////////////////////////////////////////////////////

    public function getUserFID($content, $platform)
    {
        $prefixUrl = 'https://api-ubiservices.ubi.com/v2/profiles?';
        $request_url = $prefixUrl.'profileId='.$content;

        $request_header_ubiappid = '314d4fef-e568-454a-ae06-43e3bece12a6';
        $request_header_ubisessionid = 'a651a618-bead-4732-b929-4a9488a21d27';
        $addHeaders = [
            'Referer: https://club.ubisoft.com/en-US/friends',
            'Origin: https://club.ubisoft.com',
            'expiration: null',
        ];

        $headers = array_merge(
            $this->defaultHeaders,
            $addHeaders
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $request_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $ubioutput = curl_exec($ch);
        curl_close($ch);

        $orginaloutput = $ubioutput;
        $jsonoutput = json_decode($ubioutput, true);

        return array(
            'nick' => $jsonoutput['profiles'][0]['nameOnPlatform'],
            'pid' => $jsonoutput['profiles'][0]['profileId']
        );
    }

    public function getStatsTest($content)
    {
        $uplayTicket = $this->uplayticket();

        $prefixUrl = 'https://public-ubiservices.ubi.com/v3/profiles/';
        $request_url = $prefixUrl . $content . '/friends';

        $request_header_ubiappid = '314d4fef-e568-454a-ae06-43e3bece12a6';
        $request_header_ubisessionid = 'a651a618-bead-4732-b929-4a9488a21d27';
        $addHeaders = [
            'authorization: ' . $uplayTicket,
            'Referer: https://connect.ubisoft.com/indexOverlay.html?t=1605173815&owner=https://www.ubisoft.com',
        ];

        $headers = array_merge(
            $this->defaultHeaders,
            $addHeaders
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $request_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $ubioutput = curl_exec($ch);
        curl_close($ch);

        $jsonoutput = json_decode($ubioutput, true);

        return $jsonoutput;
    }

    ///////////////////////////////////////////////////////////////////////////////

    public function getServerStatus()
    {
        $prefixUrl = 'https://game-status-api.ubisoft.com/v1/instances?appIds=';

        $request_url_uplay = $prefixUrl . 'e3d5ea9e-50bd-43b7-88bf-39794f4e3d40';
        $request_url_psn = $prefixUrl . 'fb4cc4c9-2063-461d-a1e8-84a7d36525fc';
        $request_url_xbl = $prefixUrl . '4008612d-3baf-49e4-957a-33066726a7bc';

        $ch_uplay = curl_init();
        curl_setopt($ch_uplay, CURLOPT_URL, $request_url_uplay);
        curl_setopt($ch_uplay, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch_uplay, CURLOPT_ENCODING, 'gzip');
        curl_setopt($ch_uplay, CURLOPT_SSL_VERIFYPEER, false);

        $ch_psn = curl_init();
        curl_setopt($ch_psn, CURLOPT_URL, $request_url_psn);
        curl_setopt($ch_psn, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch_psn, CURLOPT_ENCODING, 'gzip');
        curl_setopt($ch_psn, CURLOPT_SSL_VERIFYPEER, false);

        $ch_xbl = curl_init();
        curl_setopt($ch_xbl, CURLOPT_URL, $request_url_xbl);
        curl_setopt($ch_xbl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch_xbl, CURLOPT_ENCODING, 'gzip');
        curl_setopt($ch_xbl, CURLOPT_SSL_VERIFYPEER, false);

        $mh = curl_multi_init();

        curl_multi_add_handle($mh, $ch_uplay);
        curl_multi_add_handle($mh, $ch_psn);
        curl_multi_add_handle($mh, $ch_xbl);

        do {
            $status = curl_multi_exec($mh, $active);
            if ($active) {
                curl_multi_select($mh);
            }
        } while ($active && $status == CURLM_OK);

        curl_multi_remove_handle($mh, $ch_uplay);
        curl_multi_remove_handle($mh, $ch_psn);
        curl_multi_remove_handle($mh, $ch_xbl);
        curl_multi_close($mh);

        $result_uplay = json_decode(curl_multi_getcontent($ch_uplay), true);
        $result_psn = json_decode(curl_multi_getcontent($ch_psn), true);
        $result_xbl = json_decode(curl_multi_getcontent($ch_xbl), true);

        return array(
            'server_uplay_status' => $result_uplay[0],
            'server_psn_status' => $result_psn[0],
            'server_xbl_status' => $result_xbl[0]
        );
    }

///////////////////////////////////////////////////////////////////////////////

    public function getStatsIndex($mode, $input, $platform, $region, $stats)
    {
        $uplayTicket = $this->uplayticket();

        $prefixUrl = 'https://public-ubiservices.ubi.com/v2/profiles?';
        if ($mode === 1 || $mode === 'bynick') {
            $input = urlencode($input);
            $request_url = $prefixUrl . 'nameOnPlatform=' . $input . '&platformType=' . $platform;
        }
        if ($mode === 2 || $mode === 'byid') {
            $request_url = $prefixUrl . 'profileId=' . $input;
        }

        $addHeaders = [
            'authorization: ' . $uplayTicket,
            'Referer: https://club.ubisoft.com/en-US/friends',
            'expiration: null',
        ];

        $headers = array_merge(
            $this->defaultHeaders,
            $addHeaders
        );

        $ch1 = curl_init();
        curl_setopt($ch1, CURLOPT_URL, $request_url);
        curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch1, CURLOPT_ENCODING, 'gzip');
        curl_setopt($ch1, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch1, CURLOPT_SSL_VERIFYPEER, false);
        $result1 = json_decode(curl_exec($ch1), true);
        curl_close($ch1);

        $profileId = $result1['profiles'][0]['profileId'];

        $prefixUrl = array(
            'uplay' => 'https://public-ubiservices.ubi.com/v1/spaces/5172a557-50b5-4665-b7db-e3f2e8c5041d/sandboxes/OSBOR_PC_LNCH_A/r6karma/player_skill_records',
            'xbl' => 'https://public-ubiservices.ubi.com/v1/spaces/98a601e5-ca91-4440-b1c5-753f601a2c90/sandboxes/OSBOR_XBOXONE_LNCH_A/r6karma/player_skill_records',
            'psn' => 'https://public-ubiservices.ubi.com/v1/spaces/05bfb3f7-6c21-4c42-be1f-97a33fb5cf66/sandboxes/OSBOR_PS4_LNCH_A/r6karma/player_skill_records'
        );

        $request_url = $prefixUrl[$platform] . '?board_ids=pvp_ranked&profile_ids=' . $profileId . '&region_ids=' . $region . '&season_ids=-1';

        $addHeaders = [
            'authorization: ' . $uplayTicket,
            'Referer: https://www.ubisoft.com/en-us/game/rainbow-six/siege/stats/seasons/' . $profileId,
            'expiration: null',
        ];

        $headers = array_merge(
            $this->defaultHeaders,
            $addHeaders
        );

        $ch2 = curl_init();
        curl_setopt($ch2, CURLOPT_URL, $request_url);
        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch2, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch2, CURLOPT_ENCODING, 'gzip');
        curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);

        $prefixUrl = array(
            'uplay' => 'https://public-ubiservices.ubi.com/v1/spaces/5172a557-50b5-4665-b7db-e3f2e8c5041d/sandboxes/OSBOR_PC_LNCH_A/playerstats2/statistics',
            'xbl' => 'https://public-ubiservices.ubi.com/v1/spaces/98a601e5-ca91-4440-b1c5-753f601a2c90/sandboxes/OSBOR_XBOXONE_LNCH_A/playerstats2/statistics',
            'psn' => 'https://public-ubiservices.ubi.com/v1/spaces/05bfb3f7-6c21-4c42-be1f-97a33fb5cf66/sandboxes/OSBOR_PS4_LNCH_A/playerstats2/statistics'
        );

        $request_url = $prefixUrl[$platform] . '?populations=' . $profileId . '&statistics=' . $stats;

        $addHeaders = [
            'authorization: ' . $uplayTicket,
            'Referer: https://game-rainbow6.ubi.com/de-de/uplay/player-statistics/' . $profileId . '/multiplayer',
            'expiration: null',
        ];

        $headers = array_merge(
            $this->defaultHeaders,
            $addHeaders
        );

        $ch3 = curl_init();
        curl_setopt($ch3, CURLOPT_URL, $request_url);
        curl_setopt($ch3, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch3, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch3, CURLOPT_ENCODING, 'gzip');
        curl_setopt($ch3, CURLOPT_SSL_VERIFYPEER, false);

        $prefixUrl = array(
            'uplay' => 'https://public-ubiservices.ubi.com/v1/spaces/5172a557-50b5-4665-b7db-e3f2e8c5041d/sandboxes/OSBOR_PC_LNCH_A/r6playerprofile/playerprofile/progressions',
            'xbl' => 'https://public-ubiservices.ubi.com/v1/spaces/98a601e5-ca91-4440-b1c5-753f601a2c90/sandboxes/OSBOR_XBOXONE_LNCH_A/r6playerprofile/playerprofile/progressions',
            'psn' => 'https://public-ubiservices.ubi.com/v1/spaces/05bfb3f7-6c21-4c42-be1f-97a33fb5cf66/sandboxes/OSBOR_PS4_LNCH_A/r6playerprofile/playerprofile/progressions'
        );

        $request_url = $prefixUrl[$platform] . '?profile_ids=' . $profileId;

        $addHeaders = [
            'authorization: ' . $uplayTicket,
            'Referer: https://game-rainbow6.ubi.com/de-de/uplay/player-statistics/' . $profileId . '/multiplayer',
            'expiration: null',
        ];

        $headers = array_merge(
            $this->defaultHeaders,
            $addHeaders
        );

        $ch4 = curl_init();
        curl_setopt($ch4, CURLOPT_URL, $request_url);
        curl_setopt($ch4, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch4, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch4, CURLOPT_ENCODING, 'gzip');
        curl_setopt($ch4, CURLOPT_SSL_VERIFYPEER, false);

        $mh = curl_multi_init();

        curl_multi_add_handle($mh, $ch2);
        curl_multi_add_handle($mh, $ch3);
        curl_multi_add_handle($mh, $ch4);

        do {
            $status = curl_multi_exec($mh, $active);
            if ($active) {
                curl_multi_select($mh);
            }
        } while ($active && $status == CURLM_OK);

        curl_multi_remove_handle($mh, $ch2);
        curl_multi_remove_handle($mh, $ch3);
        curl_multi_remove_handle($mh, $ch4);
        curl_multi_close($mh);

        $result2Raw = json_decode(curl_multi_getcontent($ch2), true);
        $result2[$profileId] = $result2Raw['seasons_player_skill_records'][0]['regions_player_skill_records'][0]['boards_player_skill_records'][0]['players_skill_records'][0];

        $result3 = json_decode(str_replace(':infinite', '', curl_multi_getcontent($ch3)), true);

        $result4 = json_decode(curl_multi_getcontent($ch4), true);


        return array_merge(
            $result1['profiles'][0],
            $result2,
            $result3['results'][$profileId],
            $result4['player_profiles'][0],
        );
    }

///////////////////////////////////////////////////////////////////////////////

    public function getStatsPlayer($mode, $input, $platform, $region, $stats)
    {
        $uplayTicket = $this->uplayticket();

        $prefixUrl = 'https://public-ubiservices.ubi.com/v2/profiles?';
        if ($mode === 1 || $mode === 'bynick') {
            $input = urlencode($input);
            $request_url = $prefixUrl . 'nameOnPlatform=' . $input . '&platformType=' . $platform;
        }
        if ($mode === 2 || $mode === 'byid') {
            $request_url = $prefixUrl . 'profileId=' . $input;
        }

        $addHeaders = [
            'authorization: ' . $uplayTicket,
            'Referer: https://club.ubisoft.com/en-US/friends',
            'expiration: null',
        ];

        $headers = array_merge(
            $this->defaultHeaders,
            $addHeaders
        );

        $ch1 = curl_init();
        curl_setopt($ch1, CURLOPT_URL, $request_url);
        curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch1, CURLOPT_ENCODING, 'gzip');
        curl_setopt($ch1, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch1, CURLOPT_SSL_VERIFYPEER, false);
        $result1 = json_decode(curl_exec($ch1), true);
        curl_close($ch1);

        $profileId = $result1['profiles'][0]['profileId'];

        $newPlatorm = [
            'uplay' => 'PC',
            'xbl' => 'XONE',
            'psn' => 'PS4'
        ];

        $prefixUrl = array(
            'uplay' => 'https://public-ubiservices.ubi.com/v1/spaces/5172a557-50b5-4665-b7db-e3f2e8c5041d/sandboxes/OSBOR_PC_LNCH_A/r6karma/player_skill_records',
            'xbl' => 'https://public-ubiservices.ubi.com/v1/spaces/98a601e5-ca91-4440-b1c5-753f601a2c90/sandboxes/OSBOR_XBOXONE_LNCH_A/r6karma/player_skill_records',
            'psn' => 'https://public-ubiservices.ubi.com/v1/spaces/05bfb3f7-6c21-4c42-be1f-97a33fb5cf66/sandboxes/OSBOR_PS4_LNCH_A/r6karma/player_skill_records'
        );

        $request_url = $prefixUrl[$platform] . '?board_ids=pvp_ranked&profile_ids=' . $profileId . '&region_ids=' . $region . '&season_ids=' . $this->seasonsS;

        $addHeaders = [
            'authorization: ' . $uplayTicket,
            'Referer: https://game-rainbow6.ubi.com/de-de/uplay/player-statistics/' . $profileId . '/multiplayer',
            'expiration: null',
        ];

        $headers = array_merge(
            $this->defaultHeaders,
            $addHeaders
        );

        $ch2 = curl_init();
        curl_setopt($ch2, CURLOPT_URL, $request_url);
        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch2, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch2, CURLOPT_ENCODING, 'gzip');
        curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);

        $prefixUrl = array(
            'uplay' => 'https://public-ubiservices.ubi.com/v1/spaces/5172a557-50b5-4665-b7db-e3f2e8c5041d/sandboxes/OSBOR_PC_LNCH_A/playerstats2/statistics',
            'xbl' => 'https://public-ubiservices.ubi.com/v1/spaces/98a601e5-ca91-4440-b1c5-753f601a2c90/sandboxes/OSBOR_XBOXONE_LNCH_A/playerstats2/statistics',
            'psn' => 'https://public-ubiservices.ubi.com/v1/spaces/05bfb3f7-6c21-4c42-be1f-97a33fb5cf66/sandboxes/OSBOR_PS4_LNCH_A/playerstats2/statistics'
        );

        $request_url = $prefixUrl[$platform] . '?populations=' . $profileId . '&statistics=' . $stats;

        $ch3 = curl_init();
        curl_setopt($ch3, CURLOPT_URL, $request_url);
        curl_setopt($ch3, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch3, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch3, CURLOPT_ENCODING, 'gzip');
        curl_setopt($ch3, CURLOPT_SSL_VERIFYPEER, false);

        $prefixUrl = array(
            'uplay' => 'https://public-ubiservices.ubi.com/v1/spaces/5172a557-50b5-4665-b7db-e3f2e8c5041d/sandboxes/OSBOR_PC_LNCH_A/r6playerprofile/playerprofile/progressions',
            'xbl' => 'https://public-ubiservices.ubi.com/v1/spaces/98a601e5-ca91-4440-b1c5-753f601a2c90/sandboxes/OSBOR_XBOXONE_LNCH_A/r6playerprofile/playerprofile/progressions',
            'psn' => 'https://public-ubiservices.ubi.com/v1/spaces/05bfb3f7-6c21-4c42-be1f-97a33fb5cf66/sandboxes/OSBOR_PS4_LNCH_A/r6playerprofile/playerprofile/progressions'
        );

        $request_url = $prefixUrl[$platform] . '?profile_ids=' . $profileId;

        $ch4 = curl_init();
        curl_setopt($ch4, CURLOPT_URL, $request_url);
        curl_setopt($ch4, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch4, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch4, CURLOPT_ENCODING, 'gzip');
        curl_setopt($ch4, CURLOPT_SSL_VERIFYPEER, false);

        $weaponTypeStats = 'weapontypepvp_bullethit,weapontypepvp_chosen,weapontypepvp_dbno,weapontypepvp_dbnoassists,weapontypepvp_death,weapontypepvp_headshot,weapontypepvp_kills,weapontypepvp_killassists,weapontypepvp_bulletfired';

        $prefixUrl = array(
            'uplay' => 'https://public-ubiservices.ubi.com/v1/spaces/5172a557-50b5-4665-b7db-e3f2e8c5041d/sandboxes/OSBOR_PC_LNCH_A/playerstats2/statistics',
            'xbl' => 'https://public-ubiservices.ubi.com/v1/spaces/98a601e5-ca91-4440-b1c5-753f601a2c90/sandboxes/OSBOR_XBOXONE_LNCH_A/playerstats2/statistics',
            'psn' => 'https://public-ubiservices.ubi.com/v1/spaces/05bfb3f7-6c21-4c42-be1f-97a33fb5cf66/sandboxes/OSBOR_PS4_LNCH_A/playerstats2/statistics'
        );

        $request_url = $prefixUrl[$platform] . '?populations=' . $profileId . '&statistics=' . $weaponTypeStats;

        $ch6 = curl_init();
        curl_setopt($ch6, CURLOPT_URL, $request_url);
        curl_setopt($ch6, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch6, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch6, CURLOPT_ENCODING, 'gzip');
        curl_setopt($ch6, CURLOPT_SSL_VERIFYPEER, false);

        $request_url = 'https://r6s-stats.ubisoft.com/v1/current/weapons/' . $profileId . '?gameMode=all&platform=' . $newPlatorm[$platform] . '&teamRole=all';

        $addHeaders = [
            'authorization: ' . $uplayTicket,
            'Referer: https://www.ubisoft.com/en-us/game/rainbow-six/siege/stats/weapons/' . $profileId,
            'Host: r6s-stats.ubisoft.com',
            'TE: Trailers',
            'expiration: ' . gmdate("Y-m-d\TH:i:s.v\Z", time() + 3 * 60 * 60),
        ];

        $headers = array_merge(
            $this->defaultHeaders,
            $addHeaders
        );

        $ch7 = curl_init();
        curl_setopt($ch7, CURLOPT_URL, $request_url);
        curl_setopt($ch7, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch7, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch7, CURLOPT_ENCODING, 'gzip');
        curl_setopt($ch7, CURLOPT_SSL_VERIFYPEER, false);

        $request_url = 'https://r6s-stats.ubisoft.com/v1/current/operators/' . $profileId . '?gameMode=all&platform=' . $newPlatorm[$platform] . '&teamRole=attacker,defender';

        $addHeaders = [
            'authorization: ' . $uplayTicket,
            'Referer: https://www.ubisoft.com/en-us/game/rainbow-six/siege/stats/operators/' . $profileId,
            'Host: r6s-stats.ubisoft.com',
            'TE: Trailers',
            'expiration: ' . gmdate("Y-m-d\TH:i:s.v\Z", time() + 3 * 60 * 60),
        ];

        $headers = array_merge(
            $this->defaultHeaders,
            $addHeaders
        );

        $ch5 = curl_init();
        curl_setopt($ch5, CURLOPT_URL, $request_url);
        curl_setopt($ch5, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch5, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch5, CURLOPT_ENCODING, 'gzip');
        curl_setopt($ch5, CURLOPT_SSL_VERIFYPEER, false);

        $mh = curl_multi_init();

        curl_multi_add_handle($mh, $ch2);
        curl_multi_add_handle($mh, $ch3);
        curl_multi_add_handle($mh, $ch4);
        curl_multi_add_handle($mh, $ch5);
        curl_multi_add_handle($mh, $ch6);
        curl_multi_add_handle($mh, $ch7);

        do {
            $status = curl_multi_exec($mh, $active);
            if ($active) {
                curl_multi_select($mh);
            }
        } while ($active && $status == CURLM_OK);

        curl_multi_remove_handle($mh, $ch2);
        curl_multi_remove_handle($mh, $ch3);
        curl_multi_remove_handle($mh, $ch4);
        curl_multi_remove_handle($mh, $ch5);
        curl_multi_remove_handle($mh, $ch6);
        curl_multi_remove_handle($mh, $ch7);
        curl_multi_close($mh);

        $result2Raw = json_decode(curl_multi_getcontent($ch2), true);
        $result2['seasons'] = $result2Raw['seasons_player_skill_records'];

        $result3 = json_decode(str_replace(':infinite', '', curl_multi_getcontent($ch3)), true);

        $result4 = json_decode(curl_multi_getcontent($ch4), true);

        $result5Raw = json_decode(curl_multi_getcontent($ch5), true);
        $result5['operators'] = $result5Raw['platforms'][$newPlatorm[$platform]]['gameModes']['all']['teamRoles'];

        $result6Raw = json_decode(str_replace(':infinite', '', curl_multi_getcontent($ch6)), true);
        $result6['weapontype']['stats_raw'] = $result6Raw['results'][$profileId];

        $result7Raw = json_decode(curl_multi_getcontent($ch7), true);
        $result7['weapons'] = $result7Raw['platforms'][$newPlatorm[$platform]]['gameModes']['all']['teamRoles']['all']['weaponSlots'];

        $finalArray = array_merge(
            $result1['profiles'][0],
            $result2,
            $result3['results'][$profileId],
            $result4['player_profiles'][0],
            $result5,
            $result6,
            $result7,
        );

        return $finalArray;
    }

///////////////////////////////////////////////////////////////////////////////
    public function getPanelStats($resultSavedUsersA, $resultVotedHackersA, $resultLatestHackers, $stats)
    {
        $resultSavedUsersS = explode(':', $resultSavedUsersA);
        foreach ($resultSavedUsersS as $resultSavedUserD) {
            $resultSavedUsers[] = explode(',', $resultSavedUserD);
        }

        $resultVotedHackersS = explode(':', $resultVotedHackersA);
        foreach ($resultVotedHackersS as $resultVotedHackerD) {
            $resultVotedHackers[] = explode(',', $resultVotedHackerD);
        }

        $resultLatestHackers = explode(':', $resultLatestHackers);

        $uplayTicket = $this->uplayticket();

        $request_urls = array(
            'uplay' => 'https://public-ubiservices.ubi.com/v1/spaces/5172a557-50b5-4665-b7db-e3f2e8c5041d/sandboxes/OSBOR_PC_LNCH_A/playerstats2/statistics',
            'xbl' => 'https://public-ubiservices.ubi.com/v1/spaces/98a601e5-ca91-4440-b1c5-753f601a2c90/sandboxes/OSBOR_XBOXONE_LNCH_A/playerstats2/statistics',
            'psn' => 'https://public-ubiservices.ubi.com/v1/spaces/05bfb3f7-6c21-4c42-be1f-97a33fb5cf66/sandboxes/OSBOR_PS4_LNCH_A/playerstats2/statistics'
        );

        $request_urls3 = array(
            'uplay' => 'https://public-ubiservices.ubi.com/v1/spaces/5172a557-50b5-4665-b7db-e3f2e8c5041d/sandboxes/OSBOR_PC_LNCH_A/r6karma/players',
            'xbl' => 'https://public-ubiservices.ubi.com/v1/spaces/98a601e5-ca91-4440-b1c5-753f601a2c90/sandboxes/OSBOR_XBOXONE_LNCH_A/r6karma/players',
            'psn' => 'https://public-ubiservices.ubi.com/v1/spaces/05bfb3f7-6c21-4c42-be1f-97a33fb5cf66/sandboxes/OSBOR_PS4_LNCH_A/r6karma/players'
        );

        foreach ($resultSavedUsers as $resultSavedUser) {
            $request_url1[$resultSavedUser[0]] = $request_urls[$resultSavedUser[2]] . '?populations=' . $resultSavedUser[1] . '&statistics=' . $stats;

            $headers1[$resultSavedUser[0]] = [
                'Authorization: ' . $uplayTicket,
                'Origin: https://game-rainbow6.ubi.com',
                'Accept-Encoding: deflate, br',
                'Host: public-ubiservices.ubi.com',
                'Accept-Language: de-DE,de;q=0.9,en-US;q=0.8,en;q=0.7',
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/65.0.3325.181 Safari/537.36 OPR/52.0.2871.99',
                'Accept: application/json, text/plain, */*',
                'Ubi-AppId: 39baebad-39e5-4552-8c25-2c9b919064e2',
                'Ubi-SessionId: a4df2e5c-7fee-41ff-afe5-9d79e68e8048',
                'Referer: https://game-rainbow6.ubi.com/de-de/uplay/player-statistics/' . $resultSavedUser[1] . '/multiplayer',
                'Connection: keep-alive'
            ];

            $ch1[$resultSavedUser[0]] = curl_init();
            curl_setopt($ch1[$resultSavedUser[0]], CURLOPT_URL, $request_url1[$resultSavedUser[0]]);
            curl_setopt($ch1[$resultSavedUser[0]], CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch1[$resultSavedUser[0]], CURLOPT_HTTPHEADER, $headers1[$resultSavedUser[0]]);
            curl_setopt($ch1[$resultSavedUser[0]], CURLOPT_ENCODING, 'gzip');
            curl_setopt($ch1[$resultSavedUser[0]], CURLOPT_SSL_VERIFYPEER, false);

            $savedUsers[] = $resultSavedUser[0];
            $savedUsersPlatform[$resultSavedUser[0]] = $resultSavedUser[2];

            $headers3[$resultSavedUser[0]] =[
                'Authorization: ' . $uplayTicket,
                'Origin: https://game-rainbow6.ubi.com',
                'Accept-Encoding: gzip, deflate, br',
                'Host: public-ubiservices.ubi.com',
                'Accept-Language: de-DE,de;q=0.9,en-US;q=0.8,en;q=0.7',
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/65.0.3325.181 Safari/537.36 OPR/52.0.2871.99',
                'Accept: application/json, text/plain, */*',
                'Ubi-AppId: 39baebad-39e5-4552-8c25-2c9b919064e2',
                'Ubi-SessionId: a4df2e5c-7fee-41ff-afe5-9d79e68e8048',
                'Referer: https://game-rainbow6.ubi.com/de-de/uplay/player-statistics/' . $resultSavedUser[1] . '/multiplayer',
                'Connection: keep-alive'
            ];

            $request_url3[$resultSavedUser[0]] = $request_urls3[$resultSavedUser[2]] . '?board_id=pvp_ranked&profile_ids=' . $resultSavedUser[1] . '&region_id=emea&season_id=-1';

            $ch3[$resultSavedUser[0]] = curl_init();
            curl_setopt($ch3[$resultSavedUser[0]], CURLOPT_URL, $request_url3[$resultSavedUser[0]]);
            curl_setopt($ch3[$resultSavedUser[0]], CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch3[$resultSavedUser[0]], CURLOPT_HTTPHEADER, $headers3[$resultSavedUser[0]]);
            curl_setopt($ch3[$resultSavedUser[0]], CURLOPT_ENCODING, 'gzip');
            curl_setopt($ch3[$resultSavedUser[0]], CURLOPT_SSL_VERIFYPEER, false);
        }

        foreach ($resultVotedHackers as $resultVotedHacker) {
            $request_url2[$resultVotedHacker[0]] = $request_urls[$resultVotedHacker[2]] . '?populations=' . $resultVotedHacker[1] . '&statistics=' . $stats;

            $headers2[$resultVotedHacker[0]] = [
                'Authorization: ' . $uplayTicket,
                'Origin: https://game-rainbow6.ubi.com',
                'Accept-Encoding: deflate, br',
                'Host: public-ubiservices.ubi.com',
                'Accept-Language: de-DE,de;q=0.9,en-US;q=0.8,en;q=0.7',
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/65.0.3325.181 Safari/537.36 OPR/52.0.2871.99',
                'Accept: application/json, text/plain, */*',
                'Ubi-AppId: 39baebad-39e5-4552-8c25-2c9b919064e2',
                'Ubi-SessionId: a4df2e5c-7fee-41ff-afe5-9d79e68e8048',
                'Referer: https://game-rainbow6.ubi.com/de-de/uplay/player-statistics/' . $resultVotedHacker[1] . '/multiplayer',
                'Connection: keep-alive'
            ];

            $ch2[$resultVotedHacker[0]] = curl_init();
            curl_setopt($ch2[$resultVotedHacker[0]], CURLOPT_URL, $request_url2[$resultVotedHacker[0]]);
            curl_setopt($ch2[$resultVotedHacker[0]], CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch2[$resultVotedHacker[0]], CURLOPT_HTTPHEADER, $headers2[$resultVotedHacker[0]]);
            curl_setopt($ch2[$resultVotedHacker[0]], CURLOPT_ENCODING, 'gzip');
            curl_setopt($ch2[$resultSavedUser[0]], CURLOPT_SSL_VERIFYPEER, false);

            $votedHackers[] = $resultVotedHacker[0];
            $votedHackersPlatform[$resultVotedHacker[0]] = $resultVotedHacker[2];

            $headers4[$resultSavedUser[0]] =[
                'Authorization: ' . $uplayTicket,
                'Origin: https://game-rainbow6.ubi.com',
                'Accept-Encoding: gzip, deflate, br',
                'Host: public-ubiservices.ubi.com',
                'Accept-Language: de-DE,de;q=0.9,en-US;q=0.8,en;q=0.7',
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/65.0.3325.181 Safari/537.36 OPR/52.0.2871.99',
                'Accept: application/json, text/plain, */*',
                'Ubi-AppId: 39baebad-39e5-4552-8c25-2c9b919064e2',
                'Ubi-SessionId: a4df2e5c-7fee-41ff-afe5-9d79e68e8048',
                'Referer: https://game-rainbow6.ubi.com/de-de/uplay/player-statistics/' . $resultSavedUser[1] . '/multiplayer',
                'Connection: keep-alive'
            ];

            $request_url4[$resultSavedUser[0]] = $request_urls3[$resultSavedUser[2]] . '?board_id=pvp_ranked&profile_ids=' . $resultSavedUser[1] . '&region_id=emea&season_id=-1';

            $ch4[$resultSavedUser[0]] = curl_init();
            curl_setopt($ch4[$resultSavedUser[0]], CURLOPT_URL, $request_url4[$resultSavedUser[0]]);
            curl_setopt($ch4[$resultSavedUser[0]], CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch4[$resultSavedUser[0]], CURLOPT_HTTPHEADER, $headers4[$resultSavedUser[0]]);
            curl_setopt($ch4[$resultSavedUser[0]], CURLOPT_ENCODING, 'gzip');
            curl_setopt($ch4[$resultSavedUser[0]], CURLOPT_SSL_VERIFYPEER, false);
        }

        $mh = curl_multi_init();

        foreach ($ch1 as $ch1_) {
            curl_multi_add_handle($mh, $ch1_);
        }
        foreach ($ch2 as $ch2_) {
            curl_multi_add_handle($mh, $ch2_);
        }
        foreach ($ch3 as $ch3_) {
            curl_multi_add_handle($mh, $ch3_);
        }
        foreach ($ch4 as $ch4_) {
            curl_multi_add_handle($mh, $ch4_);
        }

        do {
            $status = curl_multi_exec($mh, $active);
            if ($active) {
                curl_multi_select($mh);
            }
        } while ($active && $status == CURLM_OK);

        foreach ($ch1 as $ch1_) {
            curl_multi_remove_handle($mh, $ch1_);
        }
        foreach ($ch2 as $ch2_) {
            curl_multi_remove_handle($mh, $ch2_);
        }
        foreach ($ch3 as $ch3_) {
            curl_multi_remove_handle($mh, $ch3_);
        }
        foreach ($ch4 as $ch4_) {
            curl_multi_remove_handle($mh, $ch4_);
        }
        curl_multi_close($mh);

        foreach ($savedUsers as $savedUser) {
            $result1[$savedUser] = json_decode(str_replace(':infinite', '', curl_multi_getcontent($ch1[$savedUser])), true)['results'];
            $result3[$savedUser] = json_decode(curl_multi_getcontent($ch3[$savedUser]), true);

            $resultId[$savedUser] = key($result1[$savedUser]);
            $result1[$savedUser] = $result1[$savedUser][$resultId[$savedUser]];
            $result1[$savedUser]['playerId'] = $resultId[$savedUser];
            $result1[$savedUser]['platform'] = $savedUsersPlatform[$savedUser];
            $result1[$savedUser]['ranked'] = $result3[$savedUser]['players'][$resultId[$savedUser]];
        }

        foreach ($votedHackers as $votedHacker) {
            $result2[$votedHacker] = json_decode(str_replace(':infinite', '', curl_multi_getcontent($ch2[$votedHacker])), true)['results'];
            $result4[$votedHacker] = json_decode(curl_multi_getcontent($ch4[$votedHacker]), true);

            $resultId[$votedHacker] = key($result2[$votedHacker]);
            $result2[$votedHacker] = $result2[$votedHacker][$resultId[$votedHacker]];
            $result2[$votedHacker]['playerId'] = $resultId[$votedHacker];
            $result2[$votedHacker]['platform'] = $votedHackersPlatform[$votedHacker];
            $result2[$votedHacker]['ranked'] = $result4[$savedUser]['players'][$resultId[$votedHacker]];
        }

        return array(
            'savedUsers' => $result1,
            'votedHackers' => $result2
        );
    }

///////////////////////////////////////////////////////////////////////////////
    public function getAPIStats($playerName, $platform, $stats)
    {
        $uplayTicket = $this->uplayticket();

        $input = urlencode($playerName);
        $request_url1 = 'https://public-ubiservices.ubi.com/v2/profiles?nameOnPlatform=' . $input . '&platformType=' . $platform;

        $request_header_ubiappid1 = '39baebad-39e5-4552-8c25-2c9b919064e2';
        $request_header_ubisessionid1 = 'a4df2e5c-7fee-41ff-afe5-9d79e68e8048';
        $headers1 =[
            'Accept: application/json, text/plain, */*',
            'ubi-appid: ' . $request_header_ubiappid1,
            'ubi-sessionid: ' . $request_header_ubisessionid1,
            'authorization: ' . $uplayTicket,
            'Referer: https://club.ubisoft.com/en-US/friends',
            'Accept-Language: de-DE,de;q=0.9,en-US;q=0.8,en;q=0.7',
            'Origin: https://game-rainbow6.ubi.com',
            'Accept-Encoding: gzip, deflate, br',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/65.0.3325.181 Safari/537.36 OPR/52.0.2871.99',
            'Host: public-ubiservices.ubi.com',
            'Pragma: no-cache',
            'Cache-Control: no-cache',
            'Connection: keep-alive'
        ];
        $ch1 = curl_init();
        curl_setopt($ch1, CURLOPT_URL, $request_url1);
        curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch1, CURLOPT_ENCODING, 'gzip');
        curl_setopt($ch1, CURLOPT_HTTPHEADER, $headers1);
        curl_setopt($ch1, CURLOPT_SSL_VERIFYPEER, false);
        $result1 = json_decode(curl_exec($ch1), true);
        curl_close($ch1);

        $profileId = $result1['profiles'][0]['profileId'];

        $request_urls2 = array(
            'uplay' => 'https://public-ubiservices.ubi.com/v1/spaces/5172a557-50b5-4665-b7db-e3f2e8c5041d/sandboxes/OSBOR_PC_LNCH_A/r6karma/players',
            'xbl' => 'https://public-ubiservices.ubi.com/v1/spaces/98a601e5-ca91-4440-b1c5-753f601a2c90/sandboxes/OSBOR_XBOXONE_LNCH_A/r6karma/players',
            'psn' => 'https://public-ubiservices.ubi.com/v1/spaces/05bfb3f7-6c21-4c42-be1f-97a33fb5cf66/sandboxes/OSBOR_PS4_LNCH_A/r6karma/players'
        );
        $request_url2 = $request_urls2[$platform] . '?board_id=pvp_ranked&profile_ids=' . $profileId . '&region_id=emea&season_id=-1';

        $headers2 =[
            'Authorization: ' . $uplayTicket,
            'Origin: https://game-rainbow6.ubi.com',
            'Accept-Encoding: gzip, deflate, br',
            'Host: public-ubiservices.ubi.com',
            'Accept-Language: de-DE,de;q=0.9,en-US;q=0.8,en;q=0.7',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/65.0.3325.181 Safari/537.36 OPR/52.0.2871.99',
            'Accept: application/json, text/plain, */*',
            'Ubi-AppId: 39baebad-39e5-4552-8c25-2c9b919064e2',
            'Ubi-SessionId: a4df2e5c-7fee-41ff-afe5-9d79e68e8048',
            'Referer: https://game-rainbow6.ubi.com/de-de/uplay/player-statistics/' . $profileId . '/multiplayer',
            'Connection: keep-alive'
        ];

        $ch2 = curl_init();
        curl_setopt($ch2, CURLOPT_URL, $request_url2);
        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch2, CURLOPT_HTTPHEADER, $headers2);
        curl_setopt($ch2, CURLOPT_ENCODING, 'gzip');
        curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);

        $request_urls3 = array(
            'uplay' => 'https://public-ubiservices.ubi.com/v1/spaces/5172a557-50b5-4665-b7db-e3f2e8c5041d/sandboxes/OSBOR_PC_LNCH_A/playerstats2/statistics',
            'xbl' => 'https://public-ubiservices.ubi.com/v1/spaces/98a601e5-ca91-4440-b1c5-753f601a2c90/sandboxes/OSBOR_XBOXONE_LNCH_A/playerstats2/statistics',
            'psn' => 'https://public-ubiservices.ubi.com/v1/spaces/05bfb3f7-6c21-4c42-be1f-97a33fb5cf66/sandboxes/OSBOR_PS4_LNCH_A/playerstats2/statistics'
        );
        $request_url3 = $request_urls3[$platform] . '?populations=' . $profileId . '&statistics=' . $stats;

        $headers3 = [
            'Authorization: ' . $uplayTicket,
            'Origin: https://game-rainbow6.ubi.com',
            'Accept-Encoding: deflate, br',
            'Host: public-ubiservices.ubi.com',
            'Accept-Language: de-DE,de;q=0.9,en-US;q=0.8,en;q=0.7',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/65.0.3325.181 Safari/537.36 OPR/52.0.2871.99',
            'Accept: application/json, text/plain, */*',
            'Ubi-AppId: 39baebad-39e5-4552-8c25-2c9b919064e2',
            'Ubi-SessionId: a4df2e5c-7fee-41ff-afe5-9d79e68e8048',
            'Referer: https://game-rainbow6.ubi.com/de-de/uplay/player-statistics/' . $profileId . '/multiplayer',
            'Connection: keep-alive'
        ];

        $ch3 = curl_init();
        curl_setopt($ch3, CURLOPT_URL, $request_url3);
        curl_setopt($ch3, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch3, CURLOPT_HTTPHEADER, $headers3);
        curl_setopt($ch3, CURLOPT_ENCODING, 'gzip');
        curl_setopt($ch3, CURLOPT_SSL_VERIFYPEER, false);

        $request_urls4 = array(
            'uplay' => 'https://public-ubiservices.ubi.com/v1/spaces/5172a557-50b5-4665-b7db-e3f2e8c5041d/sandboxes/OSBOR_PC_LNCH_A/r6playerprofile/playerprofile/progressions',
            'xbl' => 'https://public-ubiservices.ubi.com/v1/spaces/98a601e5-ca91-4440-b1c5-753f601a2c90/sandboxes/OSBOR_XBOXONE_LNCH_A/r6playerprofile/playerprofile/progressions',
            'psn' => 'https://public-ubiservices.ubi.com/v1/spaces/05bfb3f7-6c21-4c42-be1f-97a33fb5cf66/sandboxes/OSBOR_PS4_LNCH_A/r6playerprofile/playerprofile/progressions'
        );
        $request_url4 = $request_urls4[$platform] . '?profile_ids=' . $profileId;

        $headers4 =[
            'Authorization: ' . $uplayTicket,
            'Origin: https://game-rainbow6.ubi.com',
            'Accept-Encoding: deflate, br',
            'Host: public-ubiservices.ubi.com',
            'Accept-Language: de-DE,de;q=0.9,en-US;q=0.8,en;q=0.7',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/65.0.3325.181 Safari/537.36 OPR/52.0.2871.99',
            'Accept: application/json, text/plain, */*',
            'Ubi-AppId: 39baebad-39e5-4552-8c25-2c9b919064e2',
            'Ubi-SessionId: a4df2e5c-7fee-41ff-afe5-9d79e68e8048',
            'Referer: https://game-rainbow6.ubi.com/de-de/uplay/player-statistics/' . $profileId . '/multiplayer',
            'Connection: keep-alive'
        ];

        $ch4 = curl_init();
        curl_setopt($ch4, CURLOPT_URL, $request_url4);
        curl_setopt($ch4, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch4, CURLOPT_HTTPHEADER, $headers4);
        curl_setopt($ch4, CURLOPT_ENCODING, 'gzip');
        curl_setopt($ch4, CURLOPT_SSL_VERIFYPEER, false);

        $mh = curl_multi_init();

        curl_multi_add_handle($mh, $ch2);
        curl_multi_add_handle($mh, $ch3);
        curl_multi_add_handle($mh, $ch4);

        do {
            $status = curl_multi_exec($mh, $active);
            if ($active) {
                curl_multi_select($mh);
            }
        } while ($active && $status == CURLM_OK);

        curl_multi_remove_handle($mh, $ch2);
        curl_multi_remove_handle($mh, $ch3);
        curl_multi_remove_handle($mh, $ch4);
        curl_multi_close($mh);

        $result2 = json_decode(curl_multi_getcontent($ch2), true);

        $result3 = json_decode(str_replace(':infinite', '', curl_multi_getcontent($ch3)), true);

        $result4 = json_decode(curl_multi_getcontent($ch4), true);

        return array_merge(
            $result1['profiles'][0],
            $result2['players'][$profileId],
            $result3['results'][$profileId],
            $result4['player_profiles'][0]
        );
    }
}
