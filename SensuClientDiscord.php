<?php
/*!
 * @file SensuClientDiscord.php
 * @author Sensu Development Team
 * @date 2018/03/30
 * @brief Discord用Sensuクライアント
 */

require_once __DIR__.'/Config.php';
require_once __DIR__.'/SensuClient.php';
require __DIR__.'/vendor/autoload.php';

class SensuClientDiscord
{
    /*!
     * @brief SensuプラットフォームAPIクライアント
     */
    private $sensu;

    /*!
     * @brief Discord APIクライアント
     */
    private $discord;

    /*!
     * @brief コンストラクタ
     */
    public function __construct()
    {
        $this->sensu = new \SensuDevelopmentTeam\SensuClient(Config::SENSU_PLATFORM_API_KEY);
        $this->discord = new \CharlotteDunois\Yasmin\Client(
        [
            // CPU資源節約のため
            'ws.disabledEvents' =>
            [
                'TYPING_START'
            ]
        ]);
        $this->discord->login(Config::DISCORD_API_TOKEN);
    }

    /*!
     * @brief クライアントを実行
     */
    public function run()
    {
        $self = $this;
        \mpyw\Co\Co::wait($this->discord->on('message', function ($message) use ($self) {
            // 接頭辞
            $prefix = './';
            // 本文の先頭が接頭辞でなければ中止
            if (strncmp($message->content, $prefix, strlen($prefix)))
            {
                return;
            }
            // 接頭辞削除
            $command = substr($message->content, strlen($prefix), strlen($message->content) - strlen($prefix));
            // 命令分解
            $command = $self::getCommandFromText($command);

            // 投げ銭コマンド
            if (isset($command[0]) && strcasecmp($command[0], 'tip') == 0)
            {
                if (isset($command[3]))
                {
                    preg_match('/<@!?([0-9]*)>/', $command[3], $matches);
                    if (count($matches) != 2)
                    {
                        array_splice($command, 3, 1);
                    }
                    else
                    {
                        $command[3] = $matches[1];
                    }
                }
            }

            // 命令を送信
            $result = $self->sensu->command($message->author->id, $command);
            // 表示用メッセージが設定されていなければ内部エラー
            if (!isset($result->message))
            {
                $message->reply("内部エラーが発生しました。\nAn internal error occurred.");
                return;
            }

            // プッシュメッセージ
            if (isset($result->push_message))
            {
                // 投げ銭コマンド
                if (isset($command[0]) && strcasecmp($command[0], 'tip') == 0)
                {
                    $self->discord->fetchUser($command[3])->then(function($user) use ($message, $result)
                    {
                        $user->createDM()->then(function($channel) use ($message, $result)
                        {
                            $channel->send(sprintf($result->push_message, '<@'.$message->author->id.'>'));
                        });
                    });
                }
            }

            // 返信
            $message->reply("\n".$result->message);
        }));

        $this->discord->getLoop()->run();
    }

    /*!
     * @brief 発言本文より命令を取得
     * @param $test 発言本文
     * @return 命令
     */
    private static function getCommandFromText($text)
    {
        $command = htmlspecialchars_decode($text, ENT_NOQUOTES);
        $result = preg_split('/[ \n](?=(?:[^\\"]*\\"[^\\"]*\\")*(?![^\\"]*\\"))/', $command, -1, PREG_SPLIT_NO_EMPTY);
        $result = str_replace('"', '', $result);
        return $result;
    }
}

$client = new SensuClientDiscord();
$client->run();
