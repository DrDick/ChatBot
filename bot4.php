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

    protected function onImBotMessageAdd(): void
    {
        if (empty($this->getChatBotConfig())) {
            return;
        }

        $this->writeToLog($_REQUEST['data']['PARAMS']['MESSAGE'], 'onImBotMessageAdd');

        if ($_REQUEST['data']['PARAMS']['CHAT_ENTITY_TYPE'] == 'LINES') {
            list($message) = explode(" ", $_REQUEST['data']['PARAMS']['MESSAGE']);

            if ($message == '1') {
                $restParams = [
                    "DIALOG_ID" => $_REQUEST['data']['PARAMS']['DIALOG_ID'],
                    "MESSAGE" => 'Пожалуйста, ожидайте, я соединяю Вас с оператором. Возможно, мне потребуется от 1 до 3х минут, оставайтесь на линии',
                ];
            }
            if ($message == '2') {
                $restParams = [
                    "DIALOG_ID" => $_REQUEST['data']['PARAMS']['DIALOG_ID'],
                    "MESSAGE" => 'Введите номер заявки (используйте # перед номером заявки)',

                ];
            }
            if ($message == '3') {
                $restParams = [
                    "DIALOG_ID" => $_REQUEST['data']['PARAMS']['DIALOG_ID'],
                    "MESSAGE" => 'Пройдите по [url=http://www.infoflot.com/#online-scoreboard/] ссылке [/url]',
                ];
            }
            if ($message == '4') {
                $restParams = [
                    "DIALOG_ID" => $_REQUEST['data']['PARAMS']['DIALOG_ID'],
                    "MESSAGE" => 'Пожалуйста, ожидайте, я соединяю Вас с оператором. Возможно, мне потребуется от 1 до 3х минут, оставайтесь на линии.',
                ];
            }
            if ($message == '5') {
                $restParams = [
                    "DIALOG_ID" => $_REQUEST['data']['PARAMS']['DIALOG_ID'],
                    "MESSAGE" => 'Перевожу на оператора',
                ];
            }
            if ($message == '6') {
                $restParams = [
                    "DIALOG_ID" => $_REQUEST['data']['PARAMS']['DIALOG_ID'],
                    "MESSAGE" => 'Перевожу на оператора',
                ];
            }
            if (substr($message, 0, 1) == '#') {
                $restParams = [
                    "DIALOG_ID" => $_REQUEST['data']['PARAMS']['DIALOG_ID'],
                    "MESSAGE" => '[send=2.1]2.1 Добавить пассажира[/send][br]'.
                        '[send=2.2]2.2 Выбрать другую каюту/рейс[/send][br]'.
                        '[send=form]2.3 Аннулировать [/send][br]',
                ];
            }
            if ($message == 'form') {
                $restParams = [
                    "DIALOG_ID" => $_REQUEST['data']['PARAMS']['DIALOG_ID'],
                    "MESSAGE" => 'Оставьте заявку https://b24-wozmby.bitrix24.site/',
                ];
            }


            if ($message == '2.1') {
                $restParams = [
                    "DIALOG_ID" => $_REQUEST['data']['PARAMS']['DIALOG_ID'],
                    "MESSAGE" => 'Пожалуйста, ожидайте, я соединяю Вас с оператором. Возможно, мне потребуется от 1 до 3х минут, оставайтесь на линии.',
                ];
            }
            if ($message == '2.2') {
                $restParams = [
                    "DIALOG_ID" => $_REQUEST['data']['PARAMS']['DIALOG_ID'],
                    "MESSAGE" => 'Пожалуйста, ожидайте, я соединяю Вас с оператором. Возможно, мне потребуется от 1 до 3х минут, оставайтесь на линии.',
                ];
            }
            if ($message == '2.3') {
                $restParams = [
                    "DIALOG_ID" => $_REQUEST['data']['PARAMS']['DIALOG_ID'],
                    "MESSAGE" => 'Пожалуйста, ожидайте, я соединяю Вас с оператором. Возможно, мне потребуется от 1 до 3х минут, оставайтесь на линии.',
                ];
            }

            if ($message == '0') {
                $restParams = [
                    "DIALOG_ID" => $_REQUEST['data']['PARAMS']['DIALOG_ID'],
                    "MESSAGE" => 'Пожалуйста, ожидайте, я соединяю Вас с оператором. Возможно, мне потребуется от 1 до 3х минут, оставайтесь на линии.',
                ];
            }/*
            else if ($message == '0') {
                $restParams = [
                    "DIALOG_ID" => $_REQUEST['data']['PARAMS']['DIALOG_ID'],
                    "MESSAGE" => 'Wait for an answer!',
                ];
            }*/
        } else {
            $latency = (time() - $_REQUEST['ts']);
            $latency = $latency > 60 ? (round($latency / 60)) . 'm' : $latency . "s";

            $restParams = [
                "DIALOG_ID" => $_REQUEST['data']['PARAMS']['DIALOG_ID'],
                "MESSAGE" => "Message from bot",
                "ATTACH" => [
                    ["MESSAGE" => "reply: " . $_REQUEST['data']['PARAMS']['MESSAGE']],
                    ["MESSAGE" => "latency: " . $latency]
                ]
            ];
        }

        if (!empty($restParams)) {
            $this->restCommand('imbot.message.add', $restParams);
        }
    }

    protected function onImBotJoinChat(): void
    {
        if (empty($this->getChatBotConfig())) {
            return;
        }

        if ($_REQUEST['data']['PARAMS']['CHAT_ENTITY_TYPE'] == 'LINES') {
            $message =
                'Выберите тему обращения:[br]' .
                '[send=1]1. Забронировать круиз[/send][br]'.
                '[send=2]2. Вопрос по заявке[/send][br]'.
                '[send=3]3. Посмотреть табло [/send][br]'.
                '[send=4]4. Вопрос по возврату[/send][br]'.
                '[send=5]5. Другой вопрос[/send][br]'.
                '[send=5]6. Я агент[/send][br]';
                '[send=#]6. Номер заявки[/send][br]';
                '[send=2.1]2.1 Добавить пассажира[/send][br]'.
                '[send=2.2]2.2 Выбрать другую каюту/рейс[/send][br]'.
                '[send=2.3]2.3 Аннулировать [/send][br]';

            $restParams = [
                "DIALOG_ID" => $_REQUEST['data']['PARAMS']['DIALOG_ID'],
                "MESSAGE" => $message,
            ];
        }
    /*
        if ($_REQUEST['data']['PARAMS']['CHAT_ENTITY_TYPE'] == 'LINES') {
            $message =
                'Выберите тему обращения:[br]' .
                '[send=2.1]2.1 Добавить пассажира[/send][br]'.
                '[send=2.2]2.2 Выбрать другую каюту/рейс[/send][br]'.
                '[send=2.3]2.3 Другой вопрос [/send][br]';
            $restParams = [
                "DIALOG_ID" => $_REQUEST['data']['PARAMS']['DIALOG_ID'],
                "MESSAGE" => $message,
            ];
        }

*/
        else {
            $restParams = [
                "DIALOG_ID" => $_REQUEST['data']['PARAMS']['DIALOG_ID'],
                "MESSAGE" => "Введите корректную команду",
                "ATTACH" => [
                    ["MESSAGE" => [$_REQUEST['data']['PARAMS']['CHAT_TYPE'] == 'P' ? 'Private instructions' : 'Chat instructions']],
                    ["MESSAGE" => [$_REQUEST['data']['PARAMS']['CHAT_TYPE'] == 'P' ? '[send=send message]Click for send[/send] or [put=something...]write something[/put]' : "[send=send message]click for send[/send] or [put=put message to textarea]click for put[/put]"]],
                ],
                "KEYBOARD" => [
                    ["TEXT" => "Help", "COMMAND" => "help"],
                ]
            ];
        }

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

    protected function imBotRegister(): ?int
    {
        $handlerBackUrl = static::getHandlerBackUrl();

        $restParams = [
            'CODE' => 'ddplanet2',
            'TYPE' => 'O',
            'EVENT_MESSAGE_ADD' => $handlerBackUrl,
            'EVENT_WELCOME_MESSAGE' => $handlerBackUrl,
            'EVENT_BOT_DELETE' => $handlerBackUrl,
            'PROPERTIES' => [
                'NAME' => 'DDPlanet Bot3',
                'LAST_NAME' => '',
                'COLOR' => 'RED',
                'EMAIL' => 'no@mail.com',
                'PERSONAL_BIRTHDAY' => '2022-05-24',
                'PERSONAL_WWW' => '',
                'PERSONAL_GENDER' => 'M',
            ],
        ];

        $result = $this->restCommand('imbot.register', $restParams);
        return (int)$result['result'] ?: null;
    }

    protected function commandRegister(int $botId, string $commandCode, array $params = []): ?int
    {
        $defaultParams = [
            'BOT_ID' => $botId,
            'COMMAND' => $commandCode,
            'COMMON' => 'N',
            'HIDDEN' => 'N',
            'EXTRANET_SUPPORT' => 'N',
            'LANG' => [],
            'EVENT_COMMAND_ADD' => static::getHandlerBackUrl()
        ];

        $result = $this->restCommand('imbot.command.register', array_merge($defaultParams, $params));
        return (int)$result['result'] ?: null;
    }

    protected function echoCommandRegister(int $botId): ?int
    {
        $commandParams = [
            'COMMON' => 'Y',
            'LANG' => [
                ['LANGUAGE_ID' => 'en', 'TITLE' => 'Get list of colors', 'PARAMS' => ''],
            ],
        ];

        return $this->commandRegister($botId, 'echo', $commandParams);
    }

    protected function echoListCommandRegister(int $botId): ?int
    {
        $commandParams = [
            'LANG' => [
                ['LANGUAGE_ID' => 'en', 'TITLE' => 'Get list of colors', 'PARAMS' => ''],
            ],
        ];

        return $this->commandRegister($botId, 'echoList', $commandParams);
    }

    protected function helpCommandRegister(int $botId): ?int
    {
        $commandParams = [
            'LANG' => [
                ['LANGUAGE_ID' => 'en', 'TITLE' => 'Get help message', 'PARAMS' => 'some text'],
            ],
        ];

        return $this->commandRegister($botId, 'help', $commandParams);
    }

    protected function commandsRegister(int $botId)
    {
        $this->echoCommandRegister($botId);
        $this->echoListCommandRegister($botId);
        $this->helpCommandRegister($botId);
    }

    protected function bindEvent(string $eventName): void
    {
        $this->restCommand('event.bind', [
            'EVENT' => $eventName,
            'HANDLER' => static::getHandlerBackUrl()
        ]);
    }

    protected function onAppInstall(): void
    {
        if (empty($botId = $this->imBotRegister())) {
            return;
        }

        $this->commandsRegister($botId);
        $this->bindEvent('OnAppUpdate');

        $appsConfig = $this->getAppsConfig();
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

    protected function onImCommandAdd(): void
    {
        if (empty($this->getChatBotConfig())) {
            return;
        }

        foreach ($_REQUEST['data']['COMMAND'] as $command) {
            switch ($command['COMMAND']) {
                case 'echo':
                    $latency = (time() - $_REQUEST['ts']);
                    $latency = $latency > 60 ? (round($latency / 60)) . 'm' : $latency . "s";

                    $this->restCommand('imbot.command.answer', [
                        "COMMAND_ID" => $command['COMMAND_ID'],
                        "MESSAGE_ID" => $command['MESSAGE_ID'],
                        "MESSAGE" => "Answer command",
                        "ATTACH" => [
                            ["MESSAGE" => "reply: /" . $command['COMMAND'] . ' ' . $command['COMMAND_PARAMS']],
                            ["MESSAGE" => "latency: " . $latency],
                        ]
                    ]);
                    break;
                case 'echoList':
                    $initList = false;

                    if (!$command['COMMAND_PARAMS']) {
                        $initList = true;
                        $command['COMMAND_PARAMS'] = 1;
                    }

                    $attach = [];
                    switch ($command['COMMAND_PARAMS']) {
                        case 1:
                            $attach[] = ["GRID" => [
                                ["VALUE" => "RED", "DISPLAY" => "LINE", "WIDTH" => 100],
                                ["VALUE" => "#df532d", "COLOR" => "#df532d", "DISPLAY" => "LINE"],
                            ]];
                            $attach[] = ["GRID" => [
                                ["VALUE" => "GRAPHITE", "DISPLAY" => "LINE", "WIDTH" => 100],
                                ["VALUE" => "#3a403e", "COLOR" => "#3a403e", "DISPLAY" => "LINE"],
                            ]];
                            break;
                        case 2:
                            $attach[] = ["GRID" => [
                                ["VALUE" => "MINT", "DISPLAY" => "LINE", "WIDTH" => 100],
                                ["VALUE" => "#4ba984", "COLOR" => "#4ba984", "DISPLAY" => "LINE"],
                            ]];
                            $attach[] = ["GRID" => [
                                ["VALUE" => "LIGHT BLUE", "DISPLAY" => "LINE", "WIDTH" => 100],
                                ["VALUE" => "#6fc8e5", "COLOR" => "#6fc8e5", "DISPLAY" => "LINE"],
                            ]];
                            break;
                        case 3:
                            $attach[] = ["GRID" => [
                                ["VALUE" => "PURPLE", "DISPLAY" => "LINE", "WIDTH" => 100],
                                ["VALUE" => "#8474c8", "COLOR" => "#8474c8", "DISPLAY" => "LINE"],
                            ]];
                            $attach[] = ["GRID" => [
                                ["VALUE" => "AQUA", "DISPLAY" => "LINE", "WIDTH" => 100],
                                ["VALUE" => "#1eb4aa", "COLOR" => "#1eb4aa", "DISPLAY" => "LINE"],
                            ]];
                            break;
                        case 4:
                            $attach[] = ["GRID" => [
                                ["VALUE" => "PINK", "DISPLAY" => "LINE", "WIDTH" => 100],
                                ["VALUE" => "#e98fa6", "COLOR" => "#e98fa6", "DISPLAY" => "LINE"],
                            ]];
                            $attach[] = ["GRID" => [
                                ["VALUE" => "LIME", "DISPLAY" => "LINE", "WIDTH" => 100],
                                ["VALUE" => "#85cb7b", "COLOR" => "#85cb7b", "DISPLAY" => "LINE"],
                            ]];
                            break;
                        case 5:
                            $attach[] = ["GRID" => [
                                ["VALUE" => "AZURE", "DISPLAY" => "LINE", "WIDTH" => 100],
                                ["VALUE" => "#29619b", "COLOR" => "#29619b", "DISPLAY" => "LINE"],
                            ]];
                            $attach[] = ["GRID" => [
                                ["VALUE" => "ORANGE", "DISPLAY" => "LINE", "WIDTH" => 100],
                                ["VALUE" => "#e8a441", "COLOR" => "#e8a441", "DISPLAY" => "LINE"],
                            ]];
                            break;

                    }

                    $keyboard = [
                        ["TEXT" => $command['COMMAND_PARAMS'] == 1 ? "· 1 ·" : "1", "COMMAND" => "echoList", "COMMAND_PARAMS" => "1", "DISPLAY" => "LINE", "BLOCK" => "Y"],
                        ["TEXT" => $command['COMMAND_PARAMS'] == 2 ? "· 2 ·" : "2", "COMMAND" => "echoList", "COMMAND_PARAMS" => "2", "DISPLAY" => "LINE", "BLOCK" => "Y"],
                        ["TEXT" => $command['COMMAND_PARAMS'] == 3 ? "· 3 ·" : "3", "COMMAND" => "echoList", "COMMAND_PARAMS" => "3", "DISPLAY" => "LINE", "BLOCK" => "Y"],
                        ["TEXT" => $command['COMMAND_PARAMS'] == 4 ? "· 4 ·" : "4", "COMMAND" => "echoList", "COMMAND_PARAMS" => "4", "DISPLAY" => "LINE", "BLOCK" => "Y"],
                        ["TEXT" => $command['COMMAND_PARAMS'] == 5 ? "· 5 ·" : "5", "COMMAND" => "echoList", "COMMAND_PARAMS" => "5", "DISPLAY" => "LINE", "BLOCK" => "Y"],
                    ];

                    if (!$initList && $command['COMMAND_CONTEXT'] == 'KEYBOARD') {
                        $this->restCommand('imbot.message.update', [
                            "BOT_ID" => $command['BOT_ID'],
                            "MESSAGE_ID" => $command['MESSAGE_ID'],
                            "ATTACH" => $attach,
                            "KEYBOARD" => $keyboard
                        ]);
                    } else {
                        $this->restCommand('imbot.command.answer', [
                            "COMMAND_ID" => $command['COMMAND_ID'],
                            "MESSAGE_ID" => $command['MESSAGE_ID'],
                            "MESSAGE" => "List of colors",
                            "ATTACH" => $attach,
                            "KEYBOARD" => $keyboard
                        ]);
                    }
                    break;
                case 'help':
                    $keyboard = [
                        [
                            "TEXT" => "Да",
                            'LINK' => "http://bitrix24.com",
                            "BG_COLOR" => "#29619b",
                            "TEXT_COLOR" => "#fff",
                            "DISPLAY" => "LINE",
                        ],
                        [
                            "TEXT" => "Верно",
                            "LINK" => "https://bitbucket.org/Bitrix24com/rest-bot-echotest",
                            "BG_COLOR" => "#2a4c7c",
                            "TEXT_COLOR" => "#fff",
                            "DISPLAY" => "LINE",
                        ],
                     /*   ["TYPE" => "NEWLINE"],
                        ["TEXT" => "Echo", "COMMAND" => "echo", "COMMAND_PARAMS" => "test from keyboard", "DISPLAY" => "LINE"],
                        ["TEXT" => "List", "COMMAND" => "echoList", "DISPLAY" => "LINE"],
                        ["TEXT" => "Help", "COMMAND" => "help", "DISPLAY" => "LINE"],*/
                    ];

                    $this->restCommand('imbot.command.answer', [
                        "COMMAND_ID" => $command['COMMAND_ID'],
                        "MESSAGE_ID" => $command['MESSAGE_ID'],
                        "MESSAGE" => "Hello! My name is EchoBot :)[br] I designed to answer your questions!",
                        "KEYBOARD" => $keyboard
                    ]);
                    break;
            }
        }
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
            case 'ONIMCOMMANDADD':
                $this->onImCommandAdd();
                break;
        }
    }
}

(new chatBot)->eventHandler();