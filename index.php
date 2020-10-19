<?php
@include_once __DIR__ . '/vendor/autoload.php';

Kirby::plugin('rasteiner/awesome-picker', [
    'options' => [
        'css-url' => 'https://kit-free.fontawesome.com/releases/latest/css/free.min.css',
        'meta-source' => 'github',
        'default-styles' => ["solid", "regular", "brands", "light", "duotone"],
        'loaded-styles' => ["solid", "regular", "brands", "light", "duotone"],
        'sprites-folder' => false
    ],
    'siteMethods' => [
        'iconSymbols' => function() {
            return rasteiner\awesomepicker\Icon::svgSymbolTable();
        }
    ],
    'fieldMethods' => [
        'toIcon' => function($field) {
            return new rasteiner\awesomepicker\Icon($field->value);
        }
    ],
    'api' => [
        'data' => [
            'icons' => function() {
                $opt = option('rasteiner.awesome-picker.meta-source');
                if(is_callable($opt)) {
                    $opt = $opt();
                }
                if(!is_string($opt)) {
                    throw new Exception("option 'rasteiner.awesome-picker.meta-source' does not evaluate to type \"string\", but \"" . gettype($opt) . "\"", 1);
                }
                if($opt === 'github') {
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
                            throw new Exception("Could not download icons metadata from github", 1);
                        }
                    }
                } else {
                    if(is_file($opt)) {
                        $filepath = $opt;
                    } else {
                        throw new Exception("Metadata YAML file for icons not fount: \"$opt\"", 1);
                    }
                }

                return Data::read($filepath);
            }
        ],
        'routes' => [
            [
                'pattern' => 'rasteiner/awesome-picker/cssurl',
                'action' => function() {
                    $opt = option('rasteiner.awesome-picker.css-url');
                    if(is_callable($opt)) {
                        $opt = $opt();
                    }
                    if(!is_string($opt)) {
                        throw new Exception("option 'rasteiner.awesome-picker.css-url' does not evaluate to type \"string\", but \"" . gettype($opt) . "\"", 1);
                    }
                    
                    return ['url' => $opt];
                }
            ],
            [
                'pattern' => 'rasteiner/awesome-picker/icons',
                'action' => function() {
                    $icons = $this->icons();
                    $data = [];

                    $loadedStyles = option('rasteiner.awesome-picker.loaded-styles');
                    if(is_string($loadedStyles)) $loadedStyles = [ $loadedStyles ];
                    $loadedStyles = array_flip($loadedStyles);

                    foreach ($icons as $name => $item) {
                        foreach ($item['styles'] as $style) {
                            if(isset($loadedStyles[$style])) {
                                $data[$style][] = [
                                    'name' => $name,
                                    'label' => $item['label'],
                                    'search' => $item['search']['terms'] ?? []
                                ];
                            }
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
                'styles' => function($styles = null) {
                    if($styles === null) {
                        $styles = option('rasteiner.awesome-picker.default-styles');
                    }
                    if(is_string($styles)) {
                        $styles = [$styles];
                    }
                    return $styles;
                }
            ]
        ]
    ]
]);