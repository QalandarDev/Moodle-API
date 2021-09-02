<?php
include "simple_html_dom.php";
require_once __DIR__ . "/myconfig.php";
class api
{
    public $url,
    $id,
    $sessionKey,
    $auth,
    $username,
    $password,
    $token,
    $cookiePath,
    $pdo,
        $result;
    public function __construct($query = [])
    {
        global $CFG;
        if (!is_dir('cookies')) {
            mkdir('cookies', 0777, true);
        }

        if (array_key_exists("username", $query)) {
            $this->username = $query["username"];
            $this->cookiePath = "cookies/" . $this->username . ".txt";
        }
        if (array_key_exists("password", $query)) {
            $this->password = $query["password"];
        }

        try {
            $this->pdo = new PDO(
                "mysql:host={$CFG->db['host']};dbname={$CFG->db['name']}",
                $CFG->db['user'],
                $CFG->db['pass']
            );
        } catch (PDOException $e) {
            echo $e->getMessage();
            error_log("Xatolik xabari:PDO: " . $e->getMessage());
        }
        if (array_key_exists("sessionkey", $query)) {
            $this->sessionKey = $query["sessionkey"];
            $this->getUsername();
        }
    }
    public function getToken()
    {
        global $CFG;
        $res = json_decode(
            file_get_contents(
                $CFG->host . "/token.php?username={$this->username}&password={$this->password}&service=moodle_mobile_app"
            ),
            true
        );
        echo $res;
        if (!array_key_exists("error", $res)) {
            unlink($this->cookiePath);
            $ch = curl_init($CFG->moodle . "/login/index.php");
            curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookiePath);
            curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookiePath);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $res = curl_exec($ch);
            $html = str_get_html($res);
            $logintoken = $html->find('input[name="logintoken"]')[0]->value;
            $this->token = $logintoken;
            return true;
        } else {
            return false;
        }
    }
    public function login()
    {
        global $CFG;
        if ($this->getToken()) {
            $query = [];
            $query["username"] = $this->username;
            $query["password"] = $this->password;
            $query["logintoken"] = $this->token;
            $query["anchor"] = "";
            $query["rememberusername"] = false;
            $ch = curl_init($CFG->moodle . "/login/index.php");
            curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookiePath);
            curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookiePath);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $res = curl_exec($ch);
            $info = curl_getinfo($ch);
            $html = str_get_html($res);
            $id = explode("=", $info["redirect_url"])[1];
            $js = [
                "ok" => true,
                "id" => $id,
            ];
            $this->addUsername();
            $this->addPassword();
            $this->addAuthToken();
            $this->addAuth(true);
            $this->addCookie();
            return $js;
        } else {
            $js = [
                "ok" => false,
                "error" => "Invalid login, please try again",
            ];
            return $js;
        }
    }
    public function logOut()
    {
        global $CFG;
        $ch = curl_init(
            $CFG->moodle . "/login/logout.php?sesskey={$this->sessionKey}"
        );
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookiePath);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookiePath);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $res = curl_exec($ch);
    }
    public function testsession($id)
    {
        global $CFG;
        $this->id = $id;
        $this->addUserId();
        $ch = curl_init($CFG->moodle . "/login/index.php?testsession=" . $id);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookiePath);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookiePath);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $res = curl_exec($ch);
        $js = curl_getinfo($ch);
        if ($js["redirect_url"] !== $CFG->moodle . "/login/index.php") {
            $js = [
                "ok" => true,
                "redirect" => "MyPage",
            ];
            return $js;
        }
    }
    public function getSessionsList()
    {
        global $CFG;
        $ch = curl_init($CFG->moodle . "/report/usersessions/user.php");
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookiePath);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookiePath);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $res = curl_exec($ch);
        // echo $res;
        $html = str_get_html($res);
        $host = $html->find('table[class="generaltable"]')[0];
        $tr = $html->find("tbody")[0]->find("tr");
        $js = [];
        $js_data = [];
        foreach ($tr as $t) {
            $js1["Log in"] = $t->find("td")[0]->innertext;
            $js1["Last access"] = $t->find("td")[1]->innertext;
            $js1["IP address"] = $t->find("td")[2]->find("a")[0]->innertext;
            $js1["id"] = $t->find("td")[3]->find("a")
            ? strstr(explode("delete=", $t->find("td")[3]->find("a")[0]->href)[1], "&", true)
            : "NULL";
            $js[] = $js1;
            $js_data[] = implode(";", $js1);
        }
        $data = implode("\n", $js_data);
        file_put_contents("sessions.txt", $data);
        return $js;
    }
    public function LogoutAll()
    {
        global $CFG;
        $ch = curl_init($CFG->moodle . "/report/usersessions/user.php");
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookiePath);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookiePath);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $res = curl_exec($ch);
        $html = str_get_html($res);
        $host = $html->find('table[class="generaltable"]')[0];
        $tr = $html->find("tbody")[0]->find("tr");
        $id = [];
        $js_data = [];
        foreach ($tr as $t) {
            $js1["Log in"] = $t->find("td")[0]->innertext;
            $js1["Last access"] = $t->find("td")[1]->innertext;
            $js1["IP address"] = $t->find("td")[2]->find("a")[0]->innertext;
            $js1["id"] = $t->find("td")[3]->find("a")
            ? strstr(explode("delete=", $t->find("td")[3]->find("a")[0]->href)[1], "&", true)
            : "NULL";
            $js[] = $js1['id'];
        }
        foreach ($js as $id) {
            $url = $CFG->moodle . "/report/usersessions/user.php?delete=" .
            $id .
            "&sesskey=" .
            $this->sessionKey;
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookiePath);
            curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookiePath);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_exec($ch);
            curl_close($ch);
        }
        return count($js);
    }
    public function getMyPage()
    {
        global $CFG;
        if (!file_exists($this->cookiePath)) {
            $js = [
                "ok" => false,
                "error" => "Cookie file not found",
            ];
            return $js;
        }
        $ch = curl_init($CFG->moodle . "/my/index.php");
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookiePath);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookiePath);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $res = curl_exec($ch);
        $html = str_get_html($res);
        //echo $html;
        if ($html->find('a[data-title="logout,moodle"]')) {
            $this->sessionKey = explode(
                "=",
                $html->find('a[data-title="logout,moodle"]')[0]->href
            )[1];
            $this->addSessionKey();
            $js = [
                "ok" => true,
            ];
            return $js;
        } else {
            $js = [
                "ok" => false,
                "error" => "Not found profile link",
            ];
            return $js;
        }
    }
    public function getCourseList()
    {
        global $CFG;
        $ch = curl_init($CFG->moodle . "/grade/report/overview/index.php");
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookiePath);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookiePath);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $res = curl_exec($ch);
        $this->addCookie();

        curl_close($ch);
        $html = str_get_html($res);
        if ($html->find("tbody")) {
            $a = $html->find("tbody")[0]->find("a");
            $js = [];
            foreach ($a as $aa) {
                $jss["id"] = explode("id=", explode("&amp;", $aa->href)[1])[1];
                $jss["name"] = $aa->innertext;
                $jss["url"] = $CFG->host . "/resource.php?sessionkey={$this->sessionKey}&id={$jss['id']}";
                $js[] = $jss;
            }
        } else {
            $js = [
                'ok' => false,
                'error' => "courses not found",
            ];
        }
        return $js;
    }
    public function getAllGrades()
    {
        global $CFG;
        $ch = curl_init($CFG->moodle . "/grade/report/overview/index.php");
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookiePath);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookiePath);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $res = curl_exec($ch);
        $this->addCookie();

        curl_close($ch);
        $html = str_get_html($res);

        if ($html->find("tbody")) {
            $tr = $html->find("tbody")[0]->find('tr[class=""]');
            $js = [];
            foreach ($tr as $row) {
                $jss["id"] = explode(
                    "id=",
                    explode("&amp;", $row->find("a")[0]->href)[1]
                )[1];
                $jss["name"] = $row->find("a")[0]->innertext;
                $jss["grade"] = $row->find("td")[1]->innertext;
                $js[] = $jss;
            }

        } else {
            $js = [
                'ok' => false,
                'error' => "Courses not found",
            ];
        }
        return $js;
    }
    public function getProfileInfo()
    {
        global $CFG;
        $ch = curl_init($CFG->moodle . "/user/edit.php");
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookiePath);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookiePath);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $res = curl_exec($ch);
        $this->addCookie();
        curl_close($ch);
        $html = str_get_html($res);
        if ($html->find('input[type="text"]')) {
            $texts = $html->find('input[type="text"]');
            $js = [];
            foreach ($texts as $text) {
                if (strlen($text->value) > 0) {
                    $js1["name"] = $text->name;
                    $js1["value"] = $text->value;
                    $js[] = $js1;
                }
            }
            return $js;
        }
    }
    public function getResourseList($id)
    {
        global $CFG;
        $ch = curl_init($CFG->moodle . "/course/view.php?id={$id}&lang=en");
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookiePath);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookiePath);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $res = curl_exec($ch);
        $this->addCookie();
        curl_close($ch);
        $html = str_get_html($res);
        if ($html->find('div[class="activityinstance]')) {
            $js = [];
            foreach ($html->find('div[class="activityinstance]') as $activity) {
                $link = explode("/", $activity->find('img')[0]->src);
                if (in_array("f", $link) and trim($activity->find('span')[1]->innertext) === "File") {
                    $jss['id'] = explode("id=", $activity->find('a')[0]->href)[1];
                    $jss['type'] = trim($activity->find('span')[1]->innertext);
                    $jss['name'] = trim(strip_tags(strstr($activity->find('span')[0]->innertext, $jss['type'], true)));
                    $jss['download'] = $CFG->host . "/getfile.php?sessionkey={$this->sessionKey}&id={$jss['id']}";
                    $js[] = $jss;
                }

            }

            return $js;
        }
    }
    public function getDownloadFile($id)
    {
        global $CFG;
        $ch = curl_init($CFG->moodle . "/mod/resource/view.php?id={$id}&redirect=true");
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookiePath);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookiePath);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $res = curl_exec($ch);
        $info = curl_getinfo($ch);
        $this->addCookie();
        curl_close($ch);
        $fh = fopen("FILE", "w");
        $ch = curl_init($info['redirect_url']);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookiePath);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookiePath);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FILE, $fh);
        curl_setopt($ch, CURLOPT_HEADERFUNCTION,
            function ($curl, $header) use (&$headers) {
                $len = strlen($header);
                $header = explode(':', $header, 2);
                if (count($header) < 2) // ignore invalid headers
                {
                    return $len;
                }

                $headers[strtolower(trim($header[0]))] = trim($header[1]);

                return $len;
            }
        );
        $res = curl_exec($ch);
        $info1 = curl_getinfo($ch);
        $headers = (is_array($headers)) ? $headers : false;
        $js = ['headers' => $headers, 'data' => $res];
        return $js;

    }
    public function getUserId()
    {
        global $CFG;
        $ch = curl_init($CFG->moodle . "/my/index.php");
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookiePath);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookiePath);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $res = curl_exec($ch);
        $html = str_get_html($res);
        if ($html) {
            if ($html->find('a[ data-title="profile,moodle"]')) {
                $url = $html->find('a[ data-title="profile,moodle"]')[0]->href;
                $id = explode("=", $url)[1];
                $this->id = $id;
                return $id;
            } else {
                return false;
            }
        }
    }
    public function addUserId()
    {
        $this->QUERY("UPDATE user SET id=? WHERE username=? LIMIT 1", [
            $this->id,
            $this->username,
        ]);
    }
    public function getSessionKey()
    {
        global $CFG;
        $ch = curl_init($CFG->moodle . "/my/index.php");
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookiePath);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookiePath);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $res = curl_exec($ch);

        $html = str_get_html($res);
        if ($html) {
            if ($html->find('a[data-title="logout,moodle"]')) {
                $url = $html->find('a[data-title="logout,moodle"]')[0]->href;
                $sessionkey = explode("=", $url)[1];
                $this->sessionKey = $sessionkey;
            }
        }
    }
    public function addSessionKey()
    {
        $this->QUERY("UPDATE user SET sessionkey=? WHERE username=? LIMIT 1", [
            $this->sessionKey,
            $this->username,
        ]);
    }
    public function QUERY(string $query, array $values)
    {
        try {
            $data = $this->pdo->prepare($query);
            $data->execute($values);
        } catch (\Exception $e) {
            error_log($e);
        }
        $result = $data->fetch(PDO::FETCH_ASSOC);
        $this->result = $result;
    }
    public function GET_ROW(string $query, array $values = null)
    {
        try {
            $data = $this->pdo->prepare($query);
            $data->execute($values);
        } catch (\Exception $e) {
            $this->PDO->rollback();
            error_log($e);
            throw $e;
        }
        if ($data) {
            $result = $data->fetchColumn();
            $this->result = $result;
        }
    }
    public function FETCH_ALL(string $query, array $values)
    {
        try {
            $data = $this->pdo->prepare($query);
            $data->execute($values);
        } catch (\Exception $e) {
            error_log($e);
        }
        $rows = [];
        while ($result = $data->fetch(PDO::FETCH_ASSOC)) {
            $rows[] = $result;
        }
        $this->result = $rows;
    }
    private function tojson()
    {
        $string = file_get_contents($this->cookiePath);
        $cookies = [];
        $lines = explode("\n", $string);
        foreach ($lines as $line) {
            if (substr_count($line, "\t") == 6) {
                $tokens = explode("\t", $line);
                $tokens = array_map("trim", $tokens);
                $cookies[] = $tokens;
            }
        }
        return $cookies;
    }
    private function totext($json)
    {
        $text = "";
        foreach ($json as $line) {
            $text .= implode("\t", $line) . "\n";
        }
        return $text;
    }
    private function addPassword()
    {
        $this->QUERY("UPDATE user SET password=? WHERE username=? LIMIT 1", [
            $this->password,
            $this->username,
        ]);
    }
    private function addUsername()
    {
        $this->GET_ROW("SELECT COUNT(*) FROM user WHERE username=? LIMIT 1", [
            $this->username,
        ]);
        if ($this->result < 1) {
            $this->QUERY("INSERT INTO user(username) VALUES(?)", [
                $this->username,
            ]);
        }
    }
    public function getUsername()
    {
        $this->addSessionKey();
        $this->QUERY("SELECT username FROM user WHERE sessionkey=? LIMIT 1", [
            $this->sessionKey,
        ]);
        $this->username = $this->result["username"];
        $this->cookiePath = "cookies/" . $this->username . ".txt";
    }
    private function addAuthToken()
    {
        $this->QUERY("UPDATE user SET authtoken=? WHERE username=? LIMIT 1", [
            $this->token,
            $this->username,
        ]);
    }
    private function addAuth($auth)
    {
        $this->QUERY("UPDATE user SET auth=? WHERE username=? LIMIT 1", [
            $auth,
            $this->username,
        ]);
    }
    private function getAuth()
    {
        $this->GET_ROW("SELECT COUNT(*) FROM user WHERE username=? LIMIT 1", [
            $this->username,
        ]);
        if ($this->result) {
            return true;
        } else {
            return false;
        }
    }
    private function addMOODLEID1_($value, $expire)
    {
        $this->QUERY(
            "UPDATE cookie SET value=?,expire=? WHERE name=? AND username=? LIMIT 1",
            [$value, $expire, "MOODLEID1_", $this->username]
        );
        unset($value);
    }
    private function addMoodleSession($value)
    {
        $this->QUERY(
            "UPDATE cookie SET value=? WHERE name=? AND username=? LIMIT 1",
            [$value, "MoodleSession", $this->username]
        );
        unset($value);
    }
    private function addCookie()
    {
        $cookies = $this->tojson();
        foreach ($cookies as $cookie) {
            $this->GET_ROW(
                "SELECT COUNT(*) FROM cookie WHERE username=? AND name=?",
                [$this->username, $cookie[5]]
            );
            if ($this->result <= 0) {
                $this->QUERY(
                    "INSERT INTO cookie(domain,subdomain,path,https,expire,name,value,username) VALUES(?,?,?,?,?,?,?,?)",
                    [
                        $cookie[0],
                        $cookie[1],
                        $cookie[2],
                        $cookie[3],
                        $cookie[4],
                        $cookie[5],
                        $cookie[6],
                        $this->username,
                    ]
                );
            } else {
                switch ($cookie[5]) {
                    case "MOODLEID1_":
                        $this->addMOODLEID1_($cookie[6], $cookie[4]);
                        break;
                    case "MoodleSession":
                        $this->addMoodleSession($cookie[6]);
                        break;
                    default:
                        error_log($cookie[5]);
                }
            }
        }
    }
    public function arrayToJson($array)
    {
        return json_encode(
            $array,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
        );
    }
}
