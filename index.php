<?php

use Kirby\Cms\App;
use Kirby\Data\Data;
use Kirby\Http\Remote;

@include_once __DIR__ . '/vendor/autoload.php';

App::plugin('rasteiner/awesome-picker', [
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
        'toIcon' => function($field, $fallback=null) {
            $icon = new rasteiner\awesomepicker\Icon($field->value);
            if($icon->isInvalid()) {
                if($fallback) {
                    if(is_string($fallback)) {
                        return new rasteiner\awesomepicker\Icon($fallback);
                    } else if (is_a($fallback, 'Kirby\\Cms\\Field')) {
                        return new rasteiner\awesomepicker\Icon($fallback->value);
                    }
                } else {
                    return null;
                }
            }
            return $icon;
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

                $cacheFolder = option('rasteiner.awesome-picker.cache-folder', kirby()->root('cache') . '/rasteiner-awesome-picker');

                if($opt === 'github') {
                    $filepath = "$cacheFolder/icons.yml";

                    if(!file_exists($filepath)) {
                        $request = Remote::get('https://raw.githubusercontent.com/FortAwesome/Font-Awesome/master/metadata/icons.yml');
                        if($request->code() === 200) {
                            if(!is_dir($cacheFolder)) {
                                mkdir($cacheFolder, 0755, true);
                            }
                            file_put_contents($filepath, $request->content());
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

                //is it a yml file?
                $ext = pathinfo($opt, PATHINFO_EXTENSION);
                if($ext === 'yml' || $ext === 'yaml') {
                    // then use and cache a json version - because it's *much* faster to parse
                    $timestamp = filemtime($opt);
                    $pathhash = md5($opt);

                    $cacheFile = "$cacheFolder/icons.$timestamp.$pathhash.json";
                    
                    if(!file_exists($cacheFile)) {
                        if(!is_dir($cacheFolder)) {
                            mkdir($cacheFolder, 0755, true);
                        }
                        $data = Data::read($opt);
                        file_put_contents($cacheFile, json_encode($data));
                        return $data;
                    } else {
                        $filepath = $cacheFile;
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