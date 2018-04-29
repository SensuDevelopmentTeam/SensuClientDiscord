<?php
/*!
 * @file SensuClient.php
 * @author Sensu Development Team
 * @date 2018/03/30
 * @brief Sensu プラットフォームAPI クライアント
 */

namespace SensuDevelopmentTeam;

class SensuClient
{
    /*!
     * @brief Sensu プラットフォームAPI 基底URL
     */
    const API_BASE_URL = 'https://sensu.tips/api/platform';

    /*!
     * @brief APIキー
     */
    private $api_key;

    /*!
     * @brief curlインスタンス
     */
    private $ch;

    /*!
     * @brief コンストラクタ
     * @param $api_key APIキー
     */
    public function __construct($api_key)
    {
        $this->api_key = $api_key;
        $this->ch = curl_init();
    }

    /*!
     * @brief デストラクタ
     */
    private function __destruct()
    {
        curl_close($this->ch);
    }

    /*!
     * @brief コマンド
     * @param $social_account ソーシャルアカウント識別
     * @param $command 命令
     * @return コマンドの返答
     */
    public function command($social_account, $command)
    {
        $imploded_command =  implode('/', array_map("urlencode", $command));
        curl_setopt_array($this->ch,
        [
            CURLOPT_URL => self::API_BASE_URL.'/'.$imploded_command,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query(['api_key' => $this->api_key, 'social_account' => $social_account])
        ]);
        $response = curl_exec($this->ch);
        return json_decode($response);
    }
}
