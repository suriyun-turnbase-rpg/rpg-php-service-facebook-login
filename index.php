<?php
$f3->route('POST /login-with-facebook', function($f3, $params) {
    // Facebook login type is 10
    $loginType = 10;
    $postBody = json_decode(urldecode($f3->get('BODY')), true);
    $userId = $postBody['userId'];
    $accessToken = $postBody['accessToken'];
    $url = "https://graph.facebook.com/".$userId."?access_token=".$accessToken."&fields=id,name,email";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    $content = curl_exec($ch);
    curl_close($ch);
    $content = str_replace("\u0040", "@", $content);
    $data = json_decode($content, true);
    $email = $data["email"];

    $output = array('error' => '');
    if (empty($email)) {
        $output['error'] = 'ERROR_EMPTY_USERNAME_OR_PASSWORD';
    } else {
        if (!IsPlayerWithUsernameFound($loginType, $email)) {
            // Make new player if not existed
            InsertNewPlayer($loginType, $email, '');
        }
        $playerAuthDb = new PlayerAuth();
        $playerAuth = $playerAuthDb->load(array(
            'username = ? AND type = ?',
            $email,
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
});
?>