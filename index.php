<?php

Kirby::plugin('rasteiner/awesome-picker', [
    'api' => [
        'data' => [
            'icons' => function() {
                $dirname = __DIR__ . '/data';
                $filepath = "$dirname/icons.yml";

                if(!file_exists($filepath)) {
                    if(!is_dir($dirname)) {
                        mkdir($dirname);
                    }
                    $request = Remote::get('https://raw.githubusercontent.com/FortAwesome/Font-Awesome/master/metadata/icons.yml');
                    if($request->code() === 200) {
                        file_put_contents($filepath, $request->content());
                        return Data::decode($request->content(), 'yaml');
                    } else {
                        throw new Exception("Could not download icons metadata", 1);
                    }
                }

                return Data::decode(file_get_contents($filepath), 'yaml');
            }
        ],
        'routes' => [
            [
                'pattern' => 'rasteiner/awesome-picker/icons',
                'action' => function() {
                    $icons = $this->icons();
                    $data = [];
                    foreach ($icons as $name => $item) {
                        foreach ($item['styles'] as $style) {
                            $data[$style][] = [
                                'name' => $name,
                                'label' => $item['label'],
                                'search' => $item['search']['terms']
                            ];
                        }
                    }
                    return $data;
                }
            ]
        ]
    ],
    'fields' => [
        'icon' => [
            'props' => [
                'styles' => function($styles = '["solid", "regular", "brands"]') {
                    if(!is_array($styles)) $styles = [$styles];
                    return data::decode($styles, 'yml');
                }
            ]
        ]
    ]
]);