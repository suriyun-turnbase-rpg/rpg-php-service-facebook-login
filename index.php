<?php
$actions['login-with-facebook'] = function($params, $postBody) {
    // Facebook login type is 10
    $loginType = 10;
    $userId = $postBody['userId'];
    $accessToken = $postBody['accessToken'];
    $url = "https://graph.facebook.com/".$userId."?access_token=".$accessToken."&fields=id";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    $content = curl_exec($ch);
    curl_close($ch);
    $content = str_replace("\u0040", "@", $content);
    $data = json_decode($content, true);
    $id = $data["id"];

    $output = array('error' => '');
    if (empty($id)) {
        $output['error'] = 'ERROR_EMPTY_USERNAME_OR_PASSWORD';
    } else {
        if (!IsPlayerWithUsernameFound($loginType, $id)) {
            // Make new player if not existed
            InsertNewPlayer($loginType, $id, '');
        }
        $playerAuthDb = new PlayerAuth();
        $playerAuth = $playerAuthDb->findone(array(
            'username = ? AND type = ?',
            $id,
            $loginType
        ));
        $playerDb = new Player();
        $player = $playerDb->load(array(
            'id = ?',
            $playerAuth->playerId
        ));
        $player = UpdatePlayerLoginToken($player);
        UpdateAllPlayerStamina($player->id);
        $output['player'] = CursorToArray($player);
    }
    echo json_encode($output);
};

if (!\Base::instance()->get('enable_action_request_query')) {
    $f3->route('POST /login-with-facebook', function($f3, $params) {
        DoPostAction('login-with-facebook', $f3, $params);
    });
}

?>
