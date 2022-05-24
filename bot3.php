<?php

$_SERVER['SERVER_PORT'] = 443;
$_REQUEST['auth']['domain'] = 'p1.infoflot.ddp-dev.ru';
$_REQUEST['auth']['client_endpoint'] = 'https://p1.infoflot.ddp-dev.ru/rest/';

class chatBot
{
    protected const CONFIG_FILE_NAME = 'config.php';

    protected array $appsConfig = [];

    protected array $authData = [];

    public function __construct()
    {
        $this->writeToLog($_REQUEST, '__construct');

        $this->setAppsConfigFromFile();
        $this->initAuthData();
    }

    protected static function getConfigFilePath(): string
    {
        return __DIR__ . '/' . self::CONFIG_FILE_NAME;
    }

    protected function setAppsConfig(array $appsConfig): void
    {
        $this->appsConfig = $appsConfig;
    }

    protected function getAppsConfig(): array
    {
        return $this->appsConfig ?: [];
    }

    protected function getConfigFromFile(): array
    {
        $configFilePath = static::getConfigFilePath();
        if (file_exists($configFilePath)) {
            include $configFilePath;
        }

        return !empty($appsConfig) && is_array($appsConfig) ? $appsConfig : [];
    }

    protected function setAuthData(array $authData): void
    {
        $this->authData = $authData;
    }

    protected function getAuthData(): array
    {
        return $this->authData ?: [];
    }

    protected function initAuthData()
    {
        $this->setAuthData($_REQUEST['auth']);
    }

    protected function getApplicationToken(): ?string
    {
        return $this->getAuthData()['application_token'];
    }

    protected function getAccessToken(): ?string
    {
        return $this->getAuthData()['access_token'];
    }

    protected function getDomain(): ?string
    {
        return $this->getAuthData()['domain'];
    }

    protected function getRestUrl(?string $method = null)
    {
        return 'https://' . $this->getDomain() . '/rest/' . $method;
    }

    protected function setAppsConfigFromFile(): void
    {
        $this->setAppsConfig($this->getConfigFromFile());
    }

    protected function getChatBotConfig(): ?array
    {
        return $this->getAppsConfig()[$this->getApplicationToken()];
    }

    protected function saveAppsConfig(array $appsConfig): void
    {
        $config = "<?php\n";
        $config .= "\$appsConfig = " . var_export($appsConfig, true) . ";\n";
        $config .= "?>";

        file_put_contents(static::getConfigFilePath(), $config);
        $this->setAppsConfig($appsConfig);
    }

    protected function unsetChatBotConfig(): void
    {
        $appsConfig = $this->getAppsConfig();
        unset($appsConfig[$this->getApplicationToken()]);

        $this->saveAppsConfig($appsConfig);
    }

    protected function writeToLog($data, string $title = ''): void
    {
        $log = "\n------------------------\n";
        $log .= date("Y.m.d G:i:s") . "\n";
        $log .= (strlen($title) > 0 ? $title : 'DEBUG') . "\n";
        $log .= print_r($data, 1);
        $log .= "\n------------------------\n";

        file_put_contents(__DIR__ . '/imbot.log', $log, FILE_APPEND);
    }

    protected function restCommand(string $method, array $params = [])
    {
        $queryUrl = $this->getRestUrl($method);
        $params = array_merge($params, ['auth' => $this->getAccessToken()]);

        $this->writeToLog(['URL' => $queryUrl, 'PARAMS' => $params], 'restCommand query');

        $queryData = http_build_query($params);

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_POST => 1,
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $queryUrl,
            CURLOPT_POSTFIELDS => $queryData,
        ]);

        $result = curl_exec($curl);
        curl_close($curl);

        $result = json_decode($result, 1);

        $this->writeToLog($result, 'restCommand result');
        return $result;
    }

    protected static function getHandlerBackUrl(): string
    {
        return ($_SERVER['SERVER_PORT'] == 443 ? 'https' : 'http') . '://' . $_SERVER['SERVER_NAME'] .
            (in_array($_SERVER['SERVER_PORT'], [80, 443]) ? '' : ':' . $_SERVER['SERVER_PORT']) . $_SERVER['SCRIPT_NAME'];
    }

    protected function getUserTasks(int $userId): array
    {
        $restParams = [
            'ORDER' => ['DEADLINE' => 'desc'],
            'FILTER' => ['RESPONSIBLE_ID' => $userId, '<DEADLINE' => '2022-05-24'],
            'PARAMS' => [],
            'SELECT' => []
        ];

        return $this->restCommand('task.item.list', $restParams) ?: [];
    }

    function getMenu ($result): array
    {

        restCommand('imbot.command.answer', Array(
           "COMMAND_ID" => $result['COMMAND_ID'],
            "MESSAGE_ID" => $result['MESSAGE_ID'],
            "MESSAGE" => "Привет! Я Инфофлот бот",
            "KEYBOARD" => Array(
                Array(
                    "TEXT" => "Bitrix24",
                    "LINK" => "http://bitrix24.com",
                    "BG_COLOR" => "#29619b",
                    "TEXT_COLOR" => "#fff",
                    "DISPLAY" => "LINE",
                ),
                Array(
                    "TEXT" => "BitBucket",
                    "LINK" => "https://bitbucket.org/Bitrix24com/rest-bot-echotest",
                    "BG_COLOR" => "#2a4c7c",
                    "TEXT_COLOR" => "#fff",
                    "DISPLAY" => "LINE",
                ),
                Array("TYPE" => "NEWLINE"), // перенос строки
                Array("TEXT" => "Echo", "COMMAND" => "echo", "COMMAND_PARAMS" => "test from keyboard", "DISPLAY" => "LINE"),
                Array("TEXT" => "List", "COMMAND" => "echoList", "DISPLAY" => "LINE"),
                Array("TEXT" => "Help", "COMMAND" => "help", "DISPLAY" => "LINE"),
            )
        ), $_REQUEST["auth"]);
    }


    function getUserTasksReport(int $userId): array
    {
        $tasks = $this->getUserTasks($userId);

        if (count($tasks['result']) > 0) {
            $arTasks = [];

            foreach ($tasks['result'] as $arTask) {
                $arTasks[] = [
                    'LINK' => [
                        'NAME' => $arTask['TITLE'],
                        'LINK' => 'https://' . $this->getDomain() . '/company/personal/user/' . $arTask['RESPONSIBLE_ID'] . '/tasks/task/view/' . $arTask['ID'] . '/'
                    ]
                ];

                $arTasks[] = [
                    'DELIMITER' => [
                        'SIZE' => 400,
                        'COLOR' => '#c6c6c6'
                    ]
                ];
            }

            $arReport = [
                'title' => 'Да, кое-какие задачи уже пролетели, например:',
                'report' => '',
                'attach' => $arTasks
            ];
        } else {
            $arReport = [
                'title' => 'Шикарно работаете!',
                'report' => 'Нечем даже огорчить - ни одной просроченной задачи',
            ];
        }

        return $arReport;
    }



    protected function getAnswer(string $message, ?int $userId = null): array
    {
        switch (mb_strtolower($message)) {
            case 'что горит':
                $arAnswer = $this->getUserTasksReport($userId);
                break;

            case '1':
                $restParams =  Array(
                    'BOT_ID' => 15273, // Идентификатор чат-бота владельца команды
                    'COMMAND' => '1', // Текст команды, которую пользователь будет вводить в чатах
                    'COMMON' => 'Y', // Если указан Y, то команда доступна во всех чатах, если N - то доступна только в тех, где присутствует чат-бот
                    'HIDDEN' => 'N', // Скрытая команда или нет - по умолчанию N
                    'EXTRANET_SUPPORT' => 'N', // Доступна ли команда пользователям Экстранет, по умолчанию N
                    'CLIENT_ID' => '', // Строковый идентификатор чат-бота, используется только в режиме Вебхуков
                    'LANG' => Array( // Массив переводов, обязательно указывать как минимум для RU и EN
                        Array('LANGUAGE_ID' => 'en', 'TITLE' => 'Get echo message', 'PARAMS' => 'some text'), // Язык, описание команды, какие данные после команды нужно вводить.
                    ),
                    'EVENT_COMMAND_ADD' => 'https://p1.infoflot.ddp-dev.ru/bot3.php', // Ссылка на обработчик для команд
                );
                $result = $this->restCommand('imbot.comand.register', $restParams);
                $arAnswer = $this->getMenu($result);
                break;

            case 'привет':
                $arAnswer = [
                    'title' => 'Привет!',
                    'report' => 'Как у тебя настроение?'
                ];
                break;

            default:
                $arAnswer = [
                    'title' => 'Туплю-с',
                    'report' => 'Не соображу, что вы хотите узнать. А может вообще не умею...',
                ];
        }

        return $arAnswer;
    }

    protected function onImBotMessageAdd(): void
    {
        if (empty($this->getChatBotConfig())) {
            return;
        }

        $this->writeToLog([
            'MESSAGE' => $_REQUEST['data']['PARAMS']['MESSAGE'],
            'USER_ID' => $_REQUEST['data']['PARAMS']['FROM_USER_ID']
        ], 'onImBotMessageAdd');

        $arAnswer = $this->getAnswer($_REQUEST['data']['PARAMS']['MESSAGE'], $_REQUEST['data']['PARAMS']['FROM_USER_ID']);
        $arAnswer['attach'][] = [
            'MESSAGE' => 'Как разберетесь с этими задачами, просто спросите еще раз [send=что горит]Что горит?[/send] и я дам новую сводку!'
        ];

        $restParams = [
            "DIALOG_ID" => $_REQUEST['data']['PARAMS']['DIALOG_ID'],
            "MESSAGE" => $arAnswer['title'] . "\n" . $arAnswer['report'] . "\n",
            "ATTACH" => $arAnswer['attach'],
        ];

        $this->restCommand('imbot.message.add', $restParams);
        $this->writeToLog($arAnswer, 'onImBotMessageAdd answer');
    }

    protected function onImBotJoinChat(): void
    {
        if (empty($this->getChatBotConfig())) {
            return;
        }
        
        $restParams = [
            'DIALOG_ID' => $_REQUEST['data']['PARAMS']['DIALOG_ID'],
            'MESSAGE' => 'Привет! Я Докладун, докладываю все как есть.',
            "ATTACH" => [['MESSAGE' => '[send=что горит]Что горит?[/send]']],
        ];

        $this->restCommand('imbot.message.add', $restParams);
        $this->writeToLog($_REQUEST['event'], 'onImBotJoinChat');
    }

    protected function onImBotDelete(): void
    {
        if (empty($this->getChatBotConfig())) {
            return;
        }

        $this->unsetChatBotConfig();
        $this->writeToLog($_REQUEST['event'], 'onImBotDelete');
    }

    protected function onAppInstall(): void
    {
        $appsConfig = $this->getAppsConfig();
        $handlerBackUrl = static::getHandlerBackUrl();

        $restParams = [
            'CODE' => 'ddplanet',
            'TYPE' => 'O',
            'EVENT_MESSAGE_ADD' => $handlerBackUrl,
            'EVENT_WELCOME_MESSAGE' => $handlerBackUrl,
            'EVENT_BOT_DELETE' => $handlerBackUrl,
            'PROPERTIES' => [
                'NAME' => 'DDPlanet Bot2',
                'LAST_NAME' => '',
                'COLOR' => 'RED',
                'EMAIL' => 'no@mail.com',
                'PERSONAL_BIRTHDAY' => '2022-05-24',
                'PERSONAL_WWW' => '',
                'PERSONAL_GENDER' => 'M',
            ],
        ];

        $result = $this->restCommand('imbot.register', $restParams);

        if (empty($botId = $result['result'])) {
            return;
        }

        $this->restCommand('event.bind', [
            'EVENT' => 'OnAppUpdate',
            'HANDLER' => $handlerBackUrl
        ]);

        $appsConfig[$this->getApplicationToken()] = [
            'BOT_ID' => $botId,
            'LANGUAGE_ID' => $_REQUEST['data']['LANGUAGE_ID'],
        ];

        $this->saveAppsConfig($appsConfig);
        $this->writeToLog($botId, 'onAppInstall');
    }

    protected function onAppUpdate(): void
    {
        if (empty($this->getChatBotConfig())) {
            return;
        }

        $result = $this->restCommand('app.info');
        $this->writeToLog($result, 'onAppUpdate');
    }

    public function eventHandler(): void
    {
        $this->writeToLog($_REQUEST['event'], 'eventHandler');

        switch ($_REQUEST['event']) {
            case 'ONIMBOTMESSAGEADD':
                $this->onImBotMessageAdd();
                break;
            case 'ONIMBOTJOINCHAT':
                $this->onImBotJoinChat();
                break;
            case 'ONIMBOTDELETE':
                $this->onImBotDelete();
                break;
            case 'ONAPPINSTALL':
                $this->onAppInstall();
                break;
            case 'ONAPPUPDATE':
                $this->onAppUpdate();
                break;
        }
    }
}

(new chatBot)->eventHandler();