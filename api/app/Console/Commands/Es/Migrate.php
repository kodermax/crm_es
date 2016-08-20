<?php

/*
 * This file is part of the Synergy package.
 *
 * Copyright (c) 2015-2016 Synergy.
 *
 * @author Maksim Karpychev <mkarpychev@synergy.ru>
 */

namespace App\Console\Commands\Es;

use Elasticsearch\ClientBuilder;
use Illuminate\Console\Command;

class Migrate extends Command
{
    protected $signature = 'es:migrate {step} {index}';

    protected $description = 'Применение настроек в Elasticsearch';

    /**
     * Выполнение команды.
     */
    public function handle()
    {
        $step = $this->argument('step');
        $index = $this->argument('index');
        call_user_func([__CLASS__, 'step' . $step], $index);
    }

    /**
     * Создание индекса portal.
     * @param $index
     */
    private static function step1($index)
    {
        $client = ClientBuilder::create()->build();

        $params = ['index' => $index];
        if ($client->indices()->exists($params)) {
            //Удаляем индекс
            $client->indices()->delete($params);
        }
        //Создаем индекс с настройками
        $params = [
            'index' => $index,
            'body' => [
                'settings' => [
                    'analysis' => [
                        'filter' => [
                            'email' => [
                                'type' => 'pattern_capture',
                                'preserve_original' => 1,
                                'patterns' => [
                                    '([^@]+)',
                                    '(\\p{L}+)',
                                    '(\\d+)',
                                    '@(.+)',
                                    '([^-@]+)',
                                ],
                            ],
                        ],
                        'analyzer' => [
                            'email' => [
                                'tokenizer' => 'uax_url_email',
                                'filter' => [
                                    'email',
                                    'lowercase',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $client->indices()->create($params);
        //mapping для даты, для сортирвоки
        #region mapping_date
        $params = [
            'index' => $index,
            'type' => 'lead',
            'body' => [
                'lead' => [
                    'properties' => [
                        'date_create' => [
                            'type' => 'date',
                            'format' => 'dd.MM.yyyy HH:mm:ss',
                            'index' => 'not_analyzed',
                        ],
                    ],
                ],
            ],
        ];
        $client->indices()->putMapping($params);
        $params = [
            'index' => $index,
            'type' => 'contact',
            'body' => [
                'contact' => [
                    'properties' => [
                        'date_create' => [
                            'type' => 'date',
                            'format' => 'dd.MM.yyyy HH:mm:ss',
                            'index' => 'not_analyzed',
                        ],
                    ],
                ],
            ],
        ];
        $client->indices()->putMapping($params);
        $params = [
            'index' => $index,
            'type' => 'deal',
            'body' => [
                'deal' => [
                    'properties' => [
                        'begin_date' => [
                            'type' => 'date',
                            'format' => 'dd.MM.yyyy',
                            'index' => 'not_analyzed',
                        ],
                    ],
                ],
            ],
        ];
        $client->indices()->putMapping($params);
        #endregion

        //perms
        #region perms
        $params = [
            'index' => $index,
            'type' => 'lead',
            'body' => [
                'lead' => [
                    'properties' => [
                        'perms' => [
                            'type' => 'string',
                            'index' => 'not_analyzed',
                        ],
                    ],
                ],
            ],
        ];
        $client->indices()->putMapping($params);
        $params = [
            'index' => $index,
            'type' => 'contact',
            'body' => [
                'contact' => [
                    'properties' => [
                        'perms' => [
                            'type' => 'string',
                            'index' => 'not_analyzed',
                        ],
                    ],
                ],
            ],
        ];
        $client->indices()->putMapping($params);
        $params = [
            'index' => $index,
            'type' => 'deal',
            'body' => [
                'deal' => [
                    'properties' => [
                        'perms' => [
                            'type' => 'string',
                            'index' => 'not_analyzed',
                        ],
                    ],
                ],
            ],
        ];
        $client->indices()->putMapping($params);
        #endregion

        #region emails
        $params = [
            'index' => $index,
            'type' => 'lead',
            'body' => [
                'lead' => [
                    'properties' => [
                        'emails' => [
                            'type' => 'string',
                            'analyzer' => 'email',
                        ],
                    ],
                ],
            ],
        ];
        $client->indices()->putMapping($params);
        $params = [
            'index' => $index,
            'type' => 'contact',
            'body' => [
                'contact' => [
                    'properties' => [
                        'emails' => [
                            'type' => 'string',
                            'analyzer' => 'email',
                        ],
                    ],
                ],
            ],
        ];
        $client->indices()->putMapping($params);
        $params = [
            'index' => $index,
            'type' => 'deal',
            'body' => [
                'deal' => [
                    'properties' => [
                        'contact.emails' => [
                            'type' => 'string',
                            'analyzer' => 'email',
                        ],
                    ],
                ],
            ],
        ];
        $client->indices()->putMapping($params);
        #endregion
    }

    private static function step2($index)
    {
        $client = ClientBuilder::create()->build();
        $params = ['index' => $index];
        $params = [
            'index' => $index,
            'type' => 'contact',
            'body' => [
                'contact' => [
                    'properties' => [
                        'emails' => [
                            'type' => 'string',
                            'analyzer' => 'email',
                        ],
                    ],
                ],
            ],
        ];
        $client->indices()->putMapping($params);
    }
}
