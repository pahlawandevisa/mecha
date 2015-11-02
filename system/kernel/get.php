<?php

class Get extends Base {

    // Apply the missing filter(s)
    protected static function AMF($data, $FP = "", $F) {
        $output = Filter::apply($F, $data);
        if(is_string($FP) && trim($FP) !== "") {
            $output = Filter::apply($FP . $F, $output);
        }
        return $output;
    }

    /**
     * ==========================================================================
     *  GET ALL FILE(S) RECURSIVELY
     * ==========================================================================
     *
     * -- CODE: -----------------------------------------------------------------
     *
     *    $files = Get::files(
     *        'some/path',
     *        'txt',
     *        'ASC',
     *        'update'
     *    );
     *
     *    $files = Get::files(
     *        'some/path',
     *        'gif,jpg,jpeg,png',
     *        'ASC',
     *        'update'
     *    );
     *
     * --------------------------------------------------------------------------
     *
     * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
     *  Parameter   | Type    | Desription
     *  ----------- | ------- | -------------------------------------------------
     *  $folder     | string  | Path to folder of file(s) you want to be listed
     *  $extensions | string  | The file extension(s)
     *  $order      | string  | Ascending or descending? ASC/DESC?
     *  $sorter     | string  | The key of array item as sorting reference
     *  $filter     | string  | Filter the resulted array by a keyword
     *  $inclusive  | boolean | Include hidden file(s) to result(s)?
     *  ----------- | ------- | -------------------------------------------------
     * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
     *
     */

    public static function files($folder = ASSET, $extensions = '*', $order = 'DESC', $sorter = 'path', $filter = "", $inclusive = false) {
        if( ! file_exists($folder)) return false;
        $results = array();
        $results_inclusive = array();
        $extension = $extensions === '*' ? '.*?' : str_replace(array(' ', ','), array("", '|'), $extensions);
        $folder = rtrim(File::path($folder), DS);
        $directory = new RecursiveDirectoryIterator($folder, FilesystemIterator::SKIP_DOTS);
        foreach(new RegexIterator(new RecursiveIteratorIterator($directory), '#\.(' . $extension . ')$#i') as $file => $object) {
            if( ! $filter) {
                $results_inclusive[] = File::inspect($file);
            } else {
                if(strpos(File::B($file), $filter) !== false) {
                    $results_inclusive[] = File::inspect($file);
                }
            }
            $_file = str_replace($folder . DS, "", $file);
            if(
                // Exclude all file(s) inside a folder from result(s) if the
                // folder name begins with two underscore(s). Example: `__folder-name`
                // Exclude file from result(s) if the file name begins with
                // two underscore(s). Example: `__file-name.txt`
                strpos($_file, '__') !== 0 &&
                // Linux?
                strpos($_file, '.') !== 0
            ) {
                if( ! $filter) {
                    $results[] = File::inspect($file);
                } else {
                    if(strpos(File::B($file), $filter) !== false) {
                        $results[] = File::inspect($file);
                    }
                }
            }
        }
        if($inclusive) {
            unset($results);
            return ! empty($results_inclusive) ? Mecha::eat($results_inclusive)->order($order, $sorter)->vomit() : false;
        } else {
            unset($results_inclusive);
            return ! empty($results) ? Mecha::eat($results)->order($order, $sorter)->vomit() : false;
        }
    }

    /**
     * ==========================================================================
     *  GET ADJACENT FILE(S)
     * ==========================================================================
     *
     * -- CODE: -----------------------------------------------------------------
     *
     *    $files = Get::adjacentFiles(
     *        'some/path',
     *        'txt',
     *        'ASC',
     *        'update'
     *    );
     *
     * --------------------------------------------------------------------------
     *
     */

    public static function adjacentFiles($folder = ASSET, $extensions = '*', $order = 'DESC', $sorter = 'path', $filter = "", $inclusive = false) {
        if( ! file_exists($folder)) return false;
        $results = array();
        $results_inclusive = array();
        $extension = str_replace(' ', "", $extensions);
        $folder = rtrim(File::path($folder), DS);
        $files = strpos($extension, ',') !== false ? glob($folder . DS . '*.{' . $extension . '}', GLOB_NOSORT | GLOB_BRACE) : glob($folder . DS . '*.' . $extension, GLOB_NOSORT);
        if($inclusive) {
            $files = array_merge($files, glob($folder . DS . '.*', GLOB_NOSORT));
        }
        foreach($files as $file) {
            if(is_file($file)) {
                if( ! $filter) {
                    $results_inclusive[] = File::inspect($file);
                } else {
                    if(strpos(File::B($file), $filter) !== false) {
                        $results_inclusive[] = File::inspect($file);
                    }
                }
                $_file = str_replace($folder . DS, "", $file);
                if(
                    strpos($_file, '__') !== 0 &&
                    strpos($_file, '.') !== 0
                ) {
                    if( ! $filter) {
                        $results[] = File::inspect($file);
                    } else {
                        if(strpos(File::B($file), $filter) !== false) {
                            $results[] = File::inspect($file);
                        }
                    }
                }
            }
        }
        if($inclusive) {
            unset($results);
            return ! empty($results_inclusive) ? Mecha::eat($results_inclusive)->order($order, $sorter)->vomit() : false;
        } else {
            unset($results_inclusive);
            return ! empty($results) ? Mecha::eat($results)->order($order, $sorter)->vomit() : false;
        }
    }

    /**
     * ==========================================================================
     *  GET ALL FILE(S) RECURSIVELY INCLUDING THE EXCLUDED FILE(S)
     * ==========================================================================
     */

    public static function inclusiveFiles($folder = ASSET, $extensions = '*', $order = 'DESC', $sorter = 'path', $filter = "") {
        return self::files($folder, $extensions, $order, $sorter, $filter, true);
    }

    /**
     * ==========================================================================
     *  GET ADJACENT FILE(S) INCLUDING THE EXCLUDED FILE(S)
     * ==========================================================================
     */

    public static function inclusiveAdjacentFiles($folder = ASSET, $extensions = '*', $order = 'DESC', $sorter = 'path', $filter = "") {
        return self::adjacentFiles($folder, $extensions, $order, $sorter, $filter, true);
    }

    // Get stored configuration data (internal only)
    public static function state_config($output = null, $fallback = array()) {
        $d = DECK . DS . 'workers' . DS . 'repair.state.config.php';
        $config = file_exists($d) ? include $d : $fallback;
        if($file = File::exist(STATE . DS . 'config.txt')) {
            Mecha::extend($config, File::open($file)->unserialize());
        }
        $config = Filter::apply('state:config', $config);
        if( ! is_null($output)) {
            return isset($config[$output]) ? $config[$output] : $fallback;
        }
        return $config;
    }

    // Get stored custom field data (internal only)
    public static function state_field($scope = null, $key = null, $fallback = array(), $all = true) {

        $config = Config::get();
        $speak = Config::speak();
        $d = DECK . DS . 'workers' . DS . 'repair.state.field.php';
        $field = file_exists($d) ? include $d : $fallback;
        if($file = File::exist(STATE . DS . 'field.txt')) {
            Mecha::extend($field, File::open($file)->unserialize());
        }

        if($all) {

            /**
             * Allow shield to add custom field(s) dynamically by creating
             * a file named as `fields.php` stored in a folder named as `workers`.
             * This file contains array of field(s) data.
             *
             * -- EXAMPLE CONTENT OF `fields.php`: --------------------------------
             *
             *    return array(
             *        'break_title_text' => array(
             *            'title' => 'Break Title Text?',
             *            'type' => 'text',
             *            'value' => "",
             *            'scope' => 'article'
             *        )
             *    );
             *
             * --------------------------------------------------------------------
             *
             */

            if($e = File::exist(SHIELD . DS . $config->shield . DS . 'workers' . DS . 'fields.php')) {
                $field_e = include $e;
                Mecha::extend($field, $field_e);
            }

            /**
             * Allow plugin to add custom field(s) dynamically by creating
             * a file named as `fields.php` stored in a folder named as `workers`.
             * This file contains array of field(s) data.
             */

            foreach(glob(PLUGIN . DS . '*' . DS . '{__launch,launch}.php', GLOB_BRACE | GLOB_NOSORT) as $active) {
                if($e = File::exist(File::D($active) . DS . 'workers' . DS . 'fields.php')) {
                    $field_e = include $e;
                    Mecha::extend($field, $field_e);
                }
            }

        }

        $field = Converter::strEval($field);

        foreach($field as &$v) {
            if( ! isset($v['value'])) $v['value'] = "";
            if( ! isset($v['scope'])) $v['scope'] = 'article,page,comment';
        }

        unset($v);

        // Filter output(s) by `scope`
        if( ! is_null($scope)) {
            $field_alt = array();
            foreach($field as $k => $v) {
                foreach(explode(',', $scope) as $s) {
                    if(strpos(',' . $v['scope'] . ',', ',' . $s . ',') !== false) {
                        $field_alt[$k] = $v;
                    }
                }
            }
            $field = $field_alt;
            unset($field_alt);
        }

        $field = Filter::apply('state:field', $field);

        // Filter output(s) by `key`
        if( ! is_null($key)) {
            return isset($field[$key]) ? $field[$key] : $fallback;
        }

        // No filter
        return $field;

    }

    // Get stored menu data (internal only)
    public static function state_menu($fallback = false) {
        $speak = Config::speak();
        $d = DECK . DS . 'workers' . DS . 'repair.state.menu.php';
        $menu = file_exists($d) ? include $d : $fallback;
        return Filter::apply('state:menu', File::open(STATE . DS . 'menu.txt')->read($menu));
    }

    // Get stored shortcode data (internal only)
    public static function state_shortcode($key = null, $fallback = array(), $all = true) {

        $config = Config::get();
        $d = DECK . DS . 'workers' . DS . 'repair.state.shortcode.php';
        $shortcode = file_exists($d) ? include $d : $fallback;
        if($file = File::exist(STATE . DS . 'shortcode.txt')) {
            $file = File::open($file)->unserialize();
            foreach($file as $k => $v) {
                unset($shortcode[$k]);
            }
            $shortcode = array_merge($shortcode, $file);
        }

        if($all) {

            /**
             * Allow shield to add custom built-in shortcode(s) dynamically
             * by creating a file named as `shortcodes.php` stored in a folder
             * named as `workers`. This file contains array of shortcode(s) data.
             *
             * -- EXAMPLE CONTENT OF `shortcodes.php`: --------------------------------
             *
             *    return array(
             *        '{{shortcode:%s}}' => '<span>\1</span>'
             *    );
             *
             * --------------------------------------------------------------------
             *
             */

            if($e = File::exist(SHIELD . DS . $config->shield . DS . 'workers' . DS . 'shortcodes.php')) {
                $shortcode_e = include $e;
                Mecha::extend($shortcode, $shortcode_e);
            }

            /**
             * Allow plugin to add custom built-in shortcode(s) dynamically
             * by creating a file named as `shortcodes.php` stored in a folder
             * named as `workers`. This file contains array of shortcode(s) data.
             */

            foreach(glob(PLUGIN . DS . '*' . DS . '{__launch,launch}.php', GLOB_BRACE | GLOB_NOSORT) as $active) {
                if($e = File::exist(File::D($active) . DS . 'workers' . DS . 'shortcodes.php')) {
                    $shortcode_e = include $e;
                    Mecha::extend($shortcode, $shortcode_e);
                }
            }

        }

        $shortcode = Filter::apply('state:shortcode', Converter::strEval($shortcode));

        // Filter output(s) by `key`
        if( ! is_null($key)) {
            return isset($shortcode[$key]) ? $shortcode[$key] : $fallback;
        }

        // No filter
        return $shortcode;

    }

    // Get stored tag data (internal only)
    public static function state_tag($id = null, $fallback = array()) {
        $speak = Config::speak();
        $d = DECK . DS . 'workers' . DS . 'repair.state.tag.php';
        $tag = file_exists($d) ? include $d : $fallback;
        $tag = File::open(STATE . DS . 'tag.txt')->unserialize($tag);
        $tag = Filter::apply('state:tag', Converter::strEval($tag));
        if( ! is_null($id)) {
            foreach($tag as $k => $v) {
                if($v['id'] === $id) {
                    return $tag[$k];
                }
            }
        }
        return $tag;
    }

    /**
     * ==========================================================================
     *  EXTRACT ARRAY OF TAG(S) FROM TAG FILE
     * ==========================================================================
     *
     * -- CODE: -----------------------------------------------------------------
     *
     *    $tags = Get::rawTags();
     *
     *    foreach($tags as $tag) {
     *        echo $tag['name'] . '<br>';
     *    }
     *
     * --------------------------------------------------------------------------
     *
     */

    public static function rawTags($order = 'ASC', $sorter = 'name') {
        $tags = self::state_tag();
        foreach($tags as $k => $v) {
            $tags[$k] = array(
                'id' => self::AMF($v['id'], 'tag:', 'id'),
                'name' => self::AMF($v['name'], 'tag:', 'name'),
                'slug' => self::AMF($v['slug'], 'tag:', 'slug'),
                'description' => self::AMF($v['description'], 'tag:', 'description')
            );
        }
        return Mecha::eat($tags)->order($order, $sorter)->vomit();
    }

    /**
     * ==========================================================================
     *  RETURN SPECIFIC TAG ITEM FILTERED BY ITS AVAILABLE DATA
     *
     *  It can be an ID, name or slug.
     * ==========================================================================
     *
     * -- CODE: -----------------------------------------------------------------
     *
     *    $tag = Get::rawTag('lorem-ipsum');
     *    echo $tag['name'] . '<br>';
     *
     * --------------------------------------------------------------------------
     *
     */

    public static function rawTag($filter, $output = null) {
        $tags = self::rawTags();
        for($i = 0, $count = count($tags); $i < $count; ++$i) {
            if((is_numeric($filter) && (int) $filter === (int) $tags[$i]['id']) || (is_string($filter) && (string) $filter === (string) $tags[$i]['name']) || (is_string($filter) && (string) $filter === (string) $tags[$i]['slug'])) {
                return ! is_null($output) ? $tags[$i][$output] : $tags[$i];
            }
        }
        return false;
    }

    /**
     * ==========================================================================
     *  `Get::rawTags()` AS OBJECT
     * ==========================================================================
     */

    public static function tags($order = 'ASC', $sorter = 'name') {
        return Mecha::O(self::rawTags($order, $sorter));
    }

    /**
     * ==========================================================================
     *  `Get::rawTag()` AS OBJECT
     * ==========================================================================
     */

    public static function tag($id_or_name_or_slug, $output = null) {
        return Mecha::O(self::rawTag($id_or_name_or_slug, $output));
    }

    /**
     * ==========================================================================
     *  GET LIST OF PAGE(S) PATH
     * ==========================================================================
     *
     * -- CODE: -----------------------------------------------------------------
     *
     *    foreach(Get::pages() as $path) {
     *        echo $path . '<br>';
     *    }
     *
     *    // [1]. Filter by Tag(s) ID
     *    Get::pages('DESC', 'kind:2');
     *    Get::pages('DESC', 'kind:2,3,4');
     *
     *    // [2]. Filter by Time
     *    Get::pages('DESC', 'time:2014');
     *    Get::pages('DESC', 'time:2014-11');
     *    Get::pages('DESC', 'time:2014-11-10');
     *
     *    // [3]. Filter by Slug
     *    Get::pages('DESC', 'slug:lorem');
     *    Get::pages('DESC', 'slug:lorem-ipsum');
     *
     *    // [4]. The Old Way(s)
     *    Get::pages('DESC', 'lorem');
     *    Get::pages('DESC', 'lorem-ipsum');
     *
     *    // [5]. The Old Way(s)' Alias
     *    Get::pages('DESC', 'keyword:lorem');
     *    Get::pages('DESC', 'keyword:lorem-ipsum');
     *
     * --------------------------------------------------------------------------
     *
     * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
     *  Parameter  | Type   | Description
     *  ---------- | ------ | ---------------------------------------------------
     *  $order     | string | Ascending or descending? ASC/DESC?
     *  $filter    | string | Filter the resulted array by a keyword
     *  $extension | string | The file extension(s)
     *  $folder    | string | Folder of the page(s)
     *  ---------- | ------ | ---------------------------------------------------
     * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
     *
     */

    public static function pages($order = 'DESC', $filter = "", $extension = 'txt', $folder = PAGE) {
        $results = array();
        $extension = str_replace(' ', "", $extension);
        $pages = strpos($extension, ',') !== false ? glob($folder . DS . '*.{' . $extension . '}', GLOB_NOSORT | GLOB_BRACE) : glob($folder . DS . '*.' . $extension, GLOB_NOSORT);
        $total_pages = count($pages);
        if( ! is_array($pages) || $total_pages === 0) return false;
        if($order === 'DESC') {
            rsort($pages);
        } else {
            sort($pages);
        }
        if( ! $filter) return $pages;
        if(strpos($filter, ':') !== false) {
            list($key, $value) = explode(':', $filter, 2);
            if($key === 'time') {
                for($i = 0; $i < $total_pages; ++$i) {
                    list($time, $kind, $slug) = explode('_', File::N($pages[$i]), 3);
                    if(strpos($time, $value) !== false) {
                        $results[] = $pages[$i];
                    }
                }
                return ! empty($results) ? $results : false;
            } else if($key === 'kind') {
                $kinds = explode(',', $value);
                for($i = 0; $i < $total_pages; ++$i) {
                    $name = str_replace('_', ',', File::N($pages[$i]));
                    foreach($kinds as $kind) {
                        if(strpos($name, ',' . $kind . ',') !== false) {
                            $results[] = $pages[$i];
                        }
                    }
                }
                return ! empty($results) ? array_unique($results) : false;
            } else if($key === 'slug') {
                for($i = 0; $i < $total_pages; ++$i) {
                    list($time, $kind, $slug) = explode('_', File::N($pages[$i]), 3);
                    if(strpos($slug, $value) !== false) {
                        $results[] = $pages[$i];
                    }
                }
                return ! empty($results) ? $results : false;
            } else { // if($key === 'keyword') {
                for($i = 0; $i < $total_pages; ++$i) {
                    if(strpos(File::N($pages[$i]), $value) !== false) {
                        $results[] = $pages[$i];
                    }
                }
                return ! empty($results) ? $results : false;
            }
        } else {
            for($i = 0; $i < $total_pages; ++$i) {
                if(strpos(File::N($pages[$i]), $filter) !== false) {
                    $results[] = $pages[$i];
                }
            }
            return ! empty($results) ? $results : false;
        }
        return false;
    }

    /**
     * ==========================================================================
     *  GET LIST OF ARTICLE(S) PATH
     * ==========================================================================
     *
     * -- CODE: -----------------------------------------------------------------
     *
     *    foreach(Get::articles() as $path) {
     *        echo $path . '<br>';
     *    }
     *
     * --------------------------------------------------------------------------
     *
     */

    public static function articles($order = 'DESC', $filter = "", $extension = 'txt') {
        return self::pages($order, $filter, $extension, ARTICLE);
    }

    /**
     * ===========================================================================
     *  GET LIST OF COMMENT(S) PATH
     * ===========================================================================
     *
     * -- CODE: ------------------------------------------------------------------
     *
     *    foreach(Get::comments() as $path) {
     *        echo $path . '<br>';
     *    }
     *
     * ---------------------------------------------------------------------------
     *
     * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
     *  Parameter  | Type    | Description
     *  ---------- | ------- | ---------------------------------------------------
     *  $post      | string  | Post time as result(s) filter
     *  $order     | string  | Ascending or descending? ASC/DESC?
     *  $extension | boolean | The file extension(s)
     *  ---------- | ------- | ---------------------------------------------------
     * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
     *
     */

    public static function comments($post = null, $order = 'ASC', $extension = 'txt') {
        $extension = str_replace(' ', "", $extension);
        if( ! is_null($post)) {
            $post = Date::format($post, 'Y-m-d-H-i-s');
            $results = strpos($extension, ',') !== false ? glob(RESPONSE . DS . $post . '_*.{' . $extension . '}', GLOB_NOSORT | GLOB_BRACE) : glob(RESPONSE . DS . $post . '_*.' . $extension, GLOB_NOSORT);
        } else {
            $results = strpos($extension, ',') !== false ? glob(RESPONSE . DS . '*.{' . $extension . '}', GLOB_NOSORT | GLOB_BRACE) : glob(RESPONSE . DS . '*.' . $extension, GLOB_NOSORT);
        }
        if( ! is_array($results) || count($results) === 0) return false;
        if($order === 'DESC') {
            rsort($results);
        } else {
            sort($results);
        }
        return $results;
    }

    /**
     * ==========================================================================
     *  GET LIST OF PAGE(S) DETAIL(S)
     * ==========================================================================
     *
     * -- CODE: -----------------------------------------------------------------
     *
     *    foreach(Get::pagesExtract() as $file) {
     *        echo $file['path'] . '<br>';
     *    }
     *
     *    Get::pagesExtract('DESC', 'time', 'kind:2');
     *    Get::pagesExtract('DESC', 'time', 'kind:2,3,4');
     *
     * --------------------------------------------------------------------------
     *
     * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
     *  Parameter | Type   | Description
     *  --------- | ------ | ----------------------------------------------------
     *  $sorter   | string | The key of array item as sorting reference
     *  --------- | ------ | ----------------------------------------------------
     * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
     *
     */

    public static function pagesExtract($order = 'DESC', $sorter = 'time', $filter = "", $extension = 'txt', $FP = 'page:', $folder = PAGE) {
        if($files = self::pages($order, $filter, $extension, $folder)) {
            $results = array();
            foreach($files as $file) {
                $results[] = self::pageExtract($file, $FP);
            }
            unset($files);
            return ! empty($results) ? Mecha::eat($results)->order($order, $sorter)->vomit() : false;
        }
        return false;
    }

    /**
     * ==========================================================================
     *  GET LIST OF ARTICLE(S) DETAIL(S)
     * ==========================================================================
     *
     * -- CODE: -----------------------------------------------------------------
     *
     *    foreach(Get::articlesExtract() as $file) {
     *        echo $file['path'] . '<br>';
     *    }
     *
     * --------------------------------------------------------------------------
     *
     */

    public static function articlesExtract($order = 'DESC', $sorter = 'time', $filter = "", $extension = 'txt') {
        return self::pagesExtract($order, $sorter, $filter, $extension, 'article:', ARTICLE);
    }

    /**
     * ==========================================================================
     *  GET LIST OF COMMENT(S) DETAIL(S)
     * ==========================================================================
     *
     * -- CODE: -----------------------------------------------------------------
     *
     *    foreach(Get::commentsExtract() as $file) {
     *        echo $file['path'] . '<br>';
     *    }
     *
     * --------------------------------------------------------------------------
     *
     * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
     *  Parameter | Type   | Description
     *  --------- | ------ | ----------------------------------------------------
     *  $sorter   | string | The key of array item as sorting reference
     *  --------- | ------ | ----------------------------------------------------
     * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
     *
     */

    public static function commentsExtract($post = null, $order = 'ASC', $sorter = 'path', $extension = 'txt') {
        if($files = self::comments($post, $order, $extension)) {
            $results = array();
            foreach($files as $file) {
                $results[] = self::commentExtract($file);
            }
            unset($files);
            return ! empty($results) ? Mecha::eat($results)->order($order, $sorter)->vomit() : false;
        }
        return false;
    }

    /**
     * ==========================================================================
     *  GET LIST OF PAGE DETAIL(S)
     * ==========================================================================
     *
     * -- CODE: -----------------------------------------------------------------
     *
     *    var_dump(Get::pageExtract($input));
     *
     * --------------------------------------------------------------------------
     *
     */

    public static function pageExtract($input, $FP = 'page:') {
        if( ! $input) return false;
        $extension = File::E($input);
        $update = File::T($input);
        $update_date = ! is_null($update) ? date('Y-m-d H:i:s', $update) : null;
        list($time, $kind, $slug) = explode('_', File::N($input), 3);
        $kind = explode(',', $kind);
        return array(
            'path' => self::AMF($input, $FP, 'path'),
            'id' => self::AMF((int) Date::format($time, 'U'), $FP, 'id'),
            'time' => self::AMF(Date::format($time), $FP, 'time'),
            'update_raw' => self::AMF($update, $FP, 'update_raw'),
            'update' => self::AMF($update_date, $FP, 'update'),
            'kind' => self::AMF(Converter::strEval($kind), $FP, 'kind'),
            'slug' => self::AMF($slug, $FP, 'slug'),
            'state' => self::AMF($extension !== 'draft' ? 'published' : 'draft', $FP, 'state')
        );
    }

    /**
     * ==========================================================================
     *  GET LIST OF ARTICLE DETAIL(S)
     * ==========================================================================
     *
     * -- CODE: -----------------------------------------------------------------
     *
     *    var_dump(Get::articleExtract($input));
     *
     * --------------------------------------------------------------------------
     *
     */

    public static function articleExtract($input) {
        return self::pageExtract($input, 'article:');
    }

    /**
     * ==========================================================================
     *  GET LIST OF COMMENT DETAIL(S)
     * ==========================================================================
     *
     * -- CODE: -----------------------------------------------------------------
     *
     *    var_dump(Get::commentExtract($input));
     *
     * --------------------------------------------------------------------------
     *
     */

    public static function commentExtract($input) {
        $FP = 'comment:';
        if( ! $input) return false;
        $extension = File::E($input);
        $update = File::T($input);
        $update_date = ! is_null($update) ? date('Y-m-d H:i:s', $update) : null;
        list($post, $id, $parent) = explode('_', File::N($input), 3);
        return array(
            'path' => self::AMF($input, $FP, 'path'),
            'time' => self::AMF(Date::format($id), $FP, 'time'),
            'update_raw' => self::AMF($update, $FP, 'update_raw'),
            'update' => self::AMF($update_date, $FP, 'update'),
            'post' => self::AMF((int) Date::format($post, 'U'), $FP, 'post'),
            'id' => self::AMF((int) Date::format($id, 'U'), $FP, 'id'),
            'parent' => self::AMF($parent === '0000-00-00-00-00-00' ? null : (int) Date::format($parent, 'U'), $FP, 'parent'),
            'state' => self::AMF($extension === 'txt' ? 'approved' : 'pending', $FP, 'state')
        );
    }

    /**
     * ==========================================================================
     *  EXTRACT PAGE FILE INTO LIST OF PAGE DATA FROM ITS PATH/SLUG/ID
     * ==========================================================================
     *
     * -- CODE: -----------------------------------------------------------------
     *
     *    var_dump(Get::page('about'));
     *
     * --------------------------------------------------------------------------
     *
     * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
     *  Parameter  | Type   | Description
     *  ---------- | ------ | ---------------------------------------------------
     *  $reference | mixed  | Slug, ID, path or array of `Get::pageExtract()`
     *  $excludes  | array  | Exclude some field(s) from result(s)
     *  $folder    | string | Folder of the page(s)
     *  $connector | string | Path connector for page URL
     *  $FP        | string | Filter prefix for `Text::toPage()`
     *  ---------- | ------ | ---------------------------------------------------
     * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
     *
     */

    public static function page($reference, $excludes = array(), $folder = PAGE, $connector = '/', $FP = 'page:') {

        $config = Config::get();
        $speak = Config::speak();

        $excludes = array_flip($excludes);
        $results = false;

        // From `Get::pageExtract()`
        if(is_array($reference)) {
            $results = $reference;
        } else {
            // By path => `cabinet\pages\0000-00-00-00-00-00_1,2,3_page-slug.txt`
            if(strpos($reference, $folder) === 0) {
                $results = self::pageExtract($reference, $FP);
            } else {
                // By slug => `page-slug` or by ID => 12345
                $results = self::pageExtract(self::pagePath($reference, $folder), $FP);
            }
        }

        if( ! $results || ! file_exists($results['path'])) return false;

        /**
         * RULES: Do not do any tags looping, content Markdown-ing
         * and external file requesting if it has been marked as
         * the excluded field(s). For better performance.
         */

        $results = $results + Text::toPage(File::open($results['path'])->read(), (isset($excludes['content']) ? false : 'content'), $FP);

        $content = isset($results['content_raw']) ? $results['content_raw'] : "";
        $time = str_replace(array(' ', ':'), '-', $results['time']);
        $extension = File::E($results['path']);

        if($php_file = File::exist(File::D($results['path']) . DS . $results['slug'] . '.php')) {
            ob_start();
            include $php_file;
            $results['content'] = ob_get_clean();
        }

        $results['date'] = self::AMF(Date::extract($results['time']), $FP, 'date');
        $results['url'] = self::AMF($config->url . $connector . $results['slug'], $FP, 'url');
        $results['excerpt'] = "";

        if( ! isset($results['link'])) $results['link'] = self::AMF("", $FP, 'link');
        if( ! isset($results['author'])) $results['author'] = self::AMF($config->author, $FP, 'author');

        if( ! isset($results['description'])) {
            $summary = Converter::curt($content, $config->excerpt_length, $config->excerpt_tail);
            $results['description'] = self::AMF($summary, $FP, 'description');
        }

        $content_test = isset($excludes['content']) && strpos($content, '<!--') !== false ? Text::toPage(Text::ES($content), 'content', $FP) : $results;
        $content_test = $content_test['content'];
        $content_test = is_array($content_test) ? implode("", $content_test) : $content_test;

        // Manual post excerpt with `<!-- cut+ "Read More" -->`
        if(strpos($content_test, '<!-- cut+ ') !== false) {
            preg_match('#<!-- cut\+( +([\'"]?)(.*?)\2)? -->#', $content_test, $matches);
            $more = ! empty($matches[3]) ? $matches[3] : $speak->read_more;
            $content_test = preg_replace('#<!-- cut\+( +(.*?))? -->#', '<p><a class="fi-link" href="' . $results['url'] . '#read-more:' . $results['id'] . '">' . $more . '</a></p><!-- cut -->', $content_test);
        }

        // ... or `<!-- cut -->`
        if(strpos($content_test, '<!-- cut -->') !== false) {
            $parts = explode('<!-- cut -->', $content_test, 2);
            $results['excerpt'] = self::AMF(trim($parts[0]), $FP, 'excerpt');
            $results['content'] = preg_replace('#<p><a class="fi-link" href=".*?">.*?<\/a><\/p>#', "", trim($parts[0])) . NL . NL . '<span class="fi" id="read-more:' . $results['id'] . '" aria-hidden="true"></span>' . NL . NL . trim($parts[1]);
        }

        if( ! isset($excludes['tags'])) {
            $tags = array();
            foreach($results['kind'] as $id) {
                $tags[] = self::rawTag($id);
            }
            $results['tags'] = self::AMF(Mecha::eat($tags)->order('ASC', 'name')->vomit(), $FP, 'tags');
        }

        if( ! isset($excludes['css']) || ! isset($excludes['js'])) {
            if($file = File::exist(CUSTOM . DS . $time . '.' . $extension)) {
                $custom = explode(SEPARATOR, File::open($file)->read());
                $css = isset($custom[0]) ? Text::DS(trim($custom[0])) : "";
                $js = isset($custom[1]) ? Text::DS(trim($custom[1])) : "";

                /**
                 * CSS
                 * ---
                 *
                 * css_raw
                 * page:css_raw
                 * custom:css_raw
                 *
                 * shortcode
                 * page:shortcode
                 * custom:shortcode
                 *
                 * css
                 * page:css
                 * custom:css
                 *
                 */

                $css = self::AMF($css, $FP, 'css_raw');
                $results['css_raw'] = Filter::apply('custom:css_raw', $css);
                $css = self::AMF($css, $FP, 'shortcode');
                $css = Filter::apply('custom:shortcode', $css);
                $css = self::AMF($css, $FP, 'css');
                $results['css'] = Filter::apply('custom:css', $css);

                /**
                 * JS
                 * --
                 *
                 * js_raw
                 * page:js_raw
                 * custom:js_raw
                 *
                 * shortcode
                 * page:shortcode
                 * custom:shortcode
                 *
                 * js
                 * page:js
                 * custom:js
                 *
                 */

                $js = self::AMF($js, $FP, 'js_raw');
                $results['js_raw'] = Filter::apply('custom:js_raw', $js);
                $js = self::AMF($js, $FP, 'shortcode');
                $js = Filter::apply('custom:shortcode', $js);
                $js = self::AMF($js, $FP, 'js');
                $results['js'] = Filter::apply('custom:js', $js);
            } else {
                $results['css'] = $results['js'] = $results['css_raw'] = $results['js_raw'] = "";
            }
            $custom = $results['css'] . $results['js'];
        } else {
            $custom = "";
        }

        $results['images'] = self::AMF(self::imagesURL($results['content'] . $custom), $FP, 'images');
        $results['image'] = self::AMF(isset($results['images'][0]) ? $results['images'][0] : Image::placeholder(), $FP, 'image');

        $comments = self::comments($results['id'], 'ASC', (Guardian::happy() ? 'txt,hold' : 'txt'));
        $results['total_comments'] = self::AMF($comments !== false ? count($comments) : 0, $FP, 'total_comments');
        $results['total_comments_text'] = self::AMF($results['total_comments'] . ' ' . ($results['total_comments'] === 1 ? $speak->comment : $speak->comments), $FP, 'total_comments_text');

        if( ! isset($excludes['comments'])) {
            if($comments) {
                $results['comments'] = array();
                foreach($comments as $comment) {
                    $results['comments'][] = self::comment($comment);
                }
                $results['comments'] = self::AMF($results['comments'], $FP, 'comments');
            }
        }

        unset($comments);


        /**
         * Custom Field(s)
         * ---------------
         */

        if( ! isset($excludes['fields'])) {

            /**
             * Initialize custom field(s) with the default value(s) so that
             * user(s) don't have to write `isset()` function multiple time(s)
             * just to prevent error message(s) because of the object key(s)
             * that is not available in the old post(s).
             */

            $fields = self::state_field(rtrim($FP, ':'), null, array(), false);

            $init = array();

            foreach($fields as $key => $value) {
                $init[$key] = $value['value'];
            }

            /**
             * Start re-writing ...
             */

            if(isset($results['fields']) && is_array($results['fields'])) {
                foreach($results['fields'] as $key => $value) {
                    // [1]. `Fields: {"my_field":{"type":"t","value":"foo"}}`
                    // [2]. `Fields: {"my_field":"foo"}`
                    if(is_array($value) && isset($value['type'])) {
                        $value = isset($value['value']) ? $value['value'] : false;
                    }
                    $init[$key] = self::AMF($value, $FP, 'fields.' . $key);
                }
            }

            $results['fields'] = $init;

            unset($fields, $init);

        }

        /**
         * Exclude some field(s) from result(s)
         */

        foreach($results as $key => $value) {
            if(isset($excludes[$key])) {
                unset($results[$key]);
            }
        }

        return Mecha::O($results);

    }

    /**
     * ==========================================================================
     *  EXTRACT ARTICLE FILE INTO LIST OF ARTICLE DATA FROM ITS SLUG/FILE PATH
     * ==========================================================================
     *
     * -- CODE: -----------------------------------------------------------------
     *
     *    var_dump(Get::article('lorem-ipsum'));
     *
     * --------------------------------------------------------------------------
     *
     */

    public static function article($reference, $excludes = array()) {
        return self::page($reference, $excludes, ARTICLE, '/' . Config::get('index')->slug . '/', 'article:');
    }

    /**
     * ===========================================================================
     *  EXTRACT COMMENT FILE INTO LIST OF COMMENT DATA FROM ITS PATH/ID/TIME/NAME
     * ===========================================================================
     *
     * -- CODE: ------------------------------------------------------------------
     *
     *    var_dump(Get::comment(1399334470));
     *
     * ---------------------------------------------------------------------------
     *
     * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
     *  Parameter  | Type   | Description
     *  ---------- | ------ | ----------------------------------------------------
     *  $reference | string | Comment path, ID, time or name
     *  ---------- | ------ | ----------------------------------------------------
     * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
     *
     */

    public static function comment($reference, $response_to = ARTICLE, $connector = null) {
        $FP = 'comment:';
        $config = Config::get();
        $results = array();
        $path = false;
        if(strpos(ROOT, $reference) === 0) { // By comment path
            $path = $reference;
        } else {
            foreach(self::comments(null, 'DESC', 'txt,hold') as $comment) {
                $base = File::B($comment);
                list($_post, $_time, $_parent) = explode('_', $base);
                if(
                    ! is_numeric($reference) && (string) File::B($reference) === (string) $base || // By comment name
                    (int) Date::format($reference, 'U') === (int) Date::format($_time, 'U') // By comment time/ID
                ) {
                    $path = $comment;
                    $results = self::commentExtract($comment);
                    break;
                }
            }
        }
        if( ! $path || ! file_exists($path)) return false;
        $results['date'] = self::AMF(Date::extract($results['time']), $FP, 'date');
        $results = $results + Text::toPage(File::open($path)->read(), 'message', 'comment:');
        $results['email'] = Text::parse($results['email'], '->decoded_html');
        $results['permalink'] = '#';
        $posts = glob($response_to . DS . '*.txt', GLOB_NOSORT);
        for($i = 0, $count = count($posts); $i < $count; ++$i) {
            list($time, $kind, $slug) = explode('_', File::N($posts[$i]), 3);
            if((int) Date::format($time, 'U') === $results['post']) {
                $results['permalink'] = self::AMF($config->url . (is_null($connector) ? '/' . $config->index->slug . '/' : $connector) . $slug . '#comment-' . $results['id'], $FP, 'permalink');
                break;
            }
        }
        if( ! isset($results['url'])) {
            $results['url'] = self::AMF('#', $FP, 'url');
        }
        $fields = self::state_field(rtrim($FP, ':'), null, array(), false);
        $init = array();
        foreach($fields as $key => $value) {
            $init[$key] = $value['value'];
        }
        if(isset($results['fields']) && is_array($results['fields'])) {
            foreach($results['fields'] as $key => $value) {
                // [1]. `Fields: {"my_field":{"type":"t","value":"foo"}}`
                // [2]. `Fields: {"my_field":"foo"}`
                if(is_array($value) && isset($value['type'])) {
                    $value = isset($value['value']) ? $value['value'] : false;
                }
                $init[$key] = self::AMF($value, $FP, 'fields.' . $key);
            }
            $results['fields'] = $init;
            unset($fields, $init);
        }
        return Mecha::O($results);
    }

    /**
     * ==========================================================================
     *  GET PAGE HEADER(S) ONLY
     * ==========================================================================
     *
     * -- CODE: -----------------------------------------------------------------
     *
     *    var_dump(Get::pageHeader('lorem-ipsum'));
     *
     * --------------------------------------------------------------------------
     *
     * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
     *  Parameter  | Type   | Description
     *  ---------- | ------ | ---------------------------------------------------
     *  $path      | string | The URL path of the page file, or a page slug
     *  $folder    | string | Folder of the page(s)
     *  $connector | string | See `Get::page()`
     *  $FP        | string | See `Get::page()`
     *  ---------- | ------ | ---------------------------------------------------
     * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
     *
     */

    public static function pageHeader($path, $folder = PAGE, $connector = '/', $FP = 'page:') {
        $config = Config::get();
        if(strpos($path, ROOT) === false) {
            $path = self::pagePath($path, $folder); // By page slug, ID or time
        }
        if( ! $path) return false;
        $results = self::pageExtract($path) + Text::toPage($path, false, $FP);
        $results['date'] = self::AMF(Date::extract($results['time']), $FP, 'date');
        $results['url'] = self::AMF($config->url . $connector . $results['slug'], $FP, 'url');
        if( ! isset($results['link'])) $results['link'] = self::AMF("", $FP, 'link');
        if( ! isset($results['author'])) $results['author'] = self::AMF($config->author, $FP, 'author');
        if( ! isset($results['description'])) $results['description'] = self::AMF("", $FP, 'description');
        $fields = self::state_field(rtrim($FP, ':'), null, array(), false);
        $init = array();
        foreach($fields as $key => $value) {
            $init[$key] = $value['value'];
        }
        if(isset($results['fields']) && is_array($results['fields'])) {
            foreach($results['fields'] as $key => $value) {
                // [1]. `Fields: {"my_field":{"type":"t","value":"foo"}}`
                // [2]. `Fields: {"my_field":"foo"}`
                if(is_array($value) && isset($value['type'])) {
                    $value = isset($value['value']) ? $value['value'] : false;
                }
                $init[$key] = self::AMF($value, $FP, 'fields.' . $key);
            }
            $results['fields'] = $init;
        }
        return Mecha::O($results);
    }

    /**
     * ==========================================================================
     *  GET ARTICLE HEADER(S) ONLY
     * ==========================================================================
     *
     * -- CODE: -----------------------------------------------------------------
     *
     *    var_dump(Get::articleHeader('lorem-ipsum'));
     *
     * --------------------------------------------------------------------------
     *
     */

    public static function articleHeader($path) {
        return self::pageHeader($path, ARTICLE, '/' . Config::get('index')->slug . '/', 'article:');
    }

    /**
     * ==========================================================================
     *  GET MINIMUM DATA OF A PAGE
     * ==========================================================================
     *
     * -- CODE: -----------------------------------------------------------------
     *
     *    var_dump(Get::pageAnchor('about'));
     *
     * --------------------------------------------------------------------------
     *
     * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
     *  Parameter  | Type   | Description
     *  ---------- | ------ | ---------------------------------------------------
     *  $path      | string | The URL path of the page file, or a page slug
     *  $folder    | string | Folder of the page(s)
     *  $connector | string | See `Get::page()`
     *  $FP        | string | See `Get::page()`
     *  ---------- | ------ | ---------------------------------------------------
     * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
     *
     */

    public static function pageAnchor($path, $folder = PAGE, $connector = '/', $FP = 'page:') {
        $config = Config::get();
        if(strpos($path, ROOT) === false) {
            $path = self::pagePath($path, $folder); // By page slug, ID or time
        }
        if($path && ($buffer = File::open($path)->get(1)) !== false) {
            $results = self::pageExtract($path);
            $parts = explode(S, $buffer, 2);
            $results['url'] = self::AMF($config->url . $connector . $results['slug'], $FP, 'url');
            $results['title'] = self::AMF((isset($parts[1]) ? Text::DS(trim($parts[1])) : ""), $FP, 'title');
            return Mecha::O($results);
        }
        return false;
    }

    /**
     * ==========================================================================
     *  GET MINIMUM DATA OF AN ARTICLE
     * ==========================================================================
     *
     * -- CODE: -----------------------------------------------------------------
     *
     *    var_dump(Get::articleAnchor('lorem-ipsum'));
     *
     * --------------------------------------------------------------------------
     *
     */

    public static function articleAnchor($path) {
        return self::pageAnchor($path, ARTICLE, '/' . Config::get('index')->slug . '/', 'article:');
    }

    /**
     * ==========================================================================
     *  GET PAGE PATH
     * ==========================================================================
     *
     * -- CODE: -----------------------------------------------------------------
     *
     *    var_dump(Get::pagePath('lorem-ipsum'));
     *
     * --------------------------------------------------------------------------
     *
     * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
     *  Parameter | Type  | Description
     *  --------- | ----- | -----------------------------------------------------
     *  $detector | mixed | Slug, ID or time of the page
     *  --------- | ----- | -----------------------------------------------------
     * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
     *
     */

    public static function pagePath($detector, $folder = PAGE) {
        foreach(glob($folder . DS . '*.{txt,draft,archive}', GLOB_NOSORT | GLOB_BRACE) as $path) {
            list($time, $kind, $slug) = explode('_', File::N($path), 3);
            if($slug === $detector || ((string) $time === Date::format($detector, 'Y-m-d-H-i-s'))) {
                return $path;
            }
        }
        return false;
    }

    /**
     * ==========================================================================
     *  GET ARTICLE PATH
     * ==========================================================================
     *
     * -- CODE: -----------------------------------------------------------------
     *
     *    var_dump(Get::articlePath('lorem-ipsum'));
     *
     * --------------------------------------------------------------------------
     *
     * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
     *  Parameter | Type  | Description
     *  --------- | ----- | -----------------------------------------------------
     *  $detector | mixed | Slug, ID or time of the article
     *  --------- | ----- | -----------------------------------------------------
     * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
     *
     */

    public static function articlePath($detector) {
        return self::pagePath($detector, ARTICLE);
    }

    /**
     * ==========================================================================
     *  GET IMAGE(S) URL FROM TEXT SOURCE
     * ==========================================================================
     *
     * -- CODE: -----------------------------------------------------------------
     *
     *    var_dump(Get::imagesURL('some text', 'no-image.png'));
     *
     * --------------------------------------------------------------------------
     *
     * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
     *  Parameter | Type   | Description
     *  --------- | ------ | ----------------------------------------------------
     *  $source   | string | The source text
     *  $fallback | string | Fallback image URL if nothing matched
     *  --------- | ------ | ----------------------------------------------------
     * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
     *
     */

    public static function imagesURL($source, $fallback = array()) {

        $config = Config::get();
        $results = array();

        /**
         * Matched with ...
         * ----------------
         *
         * [1]. `![alt text](IMAGE URL)`
         * [2]. `![alt text](IMAGE URL "optional title")`
         *
         * ... and the single-quoted version of them
         *
         */

        if(preg_match_all('#\!\[.*?\]\(([^\s]+?)( +([\'"]).*?\3)?\)#', $source, $matches)) {
            $results = array_merge($matches[1], $results);
        }

        /**
         * Matched with ...
         * ----------------
         *
         * [1]. `<img src="IMAGE URL">`
         * [2]. `<img foo="bar" src="IMAGE URL">`
         * [3]. `<img src="IMAGE URL" foo="bar">`
         * [4]. `<img src="IMAGE URL"/>`
         * [5]. `<img foo="bar" src="IMAGE URL"/>`
         * [6]. `<img src="IMAGE URL" foo="bar"/>`
         * [7]. `<img src="IMAGE URL" />`
         * [8]. `<img foo="bar" src="IMAGE URL" />`
         * [9]. `<img src="IMAGE URL" foo="bar" />`
         *
         * ... and the uppercased version of them, and the single-quoted version of them
         *
         */

        if(preg_match_all('#<img .*?src=([\'"])([^\'"]+?)\1.*? *\/?>#i', $source, $matches)) {
            $results = array_merge($matches[2], $results);
        }

        /**
         * Matched with ...
         * ----------------
         *
         * [1]. `background: url("IMAGE URL")`
         * [2]. `background-image: url("IMAGE URL")`
         * [3]. `background: foo url("IMAGE URL")`
         * [4]. `background-image: foo url("IMAGE URL")`
         * [5]. `content: url("IMAGE URL")`
         *
         * ... and the uppercased version of them, and the single-quoted version of them, and the un-quoted version of them
         *
         */

        if(preg_match_all('#(background-image|background|content)\: *.*?url\(([\'"]?)([^\'"]+?)\2\)#i', $source, $matches)) {
            $results = array_merge($matches[3], $results);
        }

        foreach(array_unique($results) as $k => $url) {
            $url = strpos($url, '/') === 0 ? $config->protocol . $config->host . $url : $url;
            if(strpos($url, Config::get('url')) === 0 && file_exists(File::path($url))) {
                $results[$k] = $url;
            } else if(strpos($url, '://') !== false) {
                $results[$k] = $url;
            }
        }

        return ! empty($results) ? $results : $fallback;

    }

    /**
     * ==========================================================================
     *  GET IMAGE URL FROM TEXT SOURCE
     * ==========================================================================
     *
     * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
     *  Parameter | Type    | Description
     *  --------- | ------- | ---------------------------------------------------
     *  $source   | string  | The source text
     *  $sequence | integer | Sequence of available image URLs
     *  $fallback | string  | Fallback image URL if nothing matched
     *  --------- | ------- | ---------------------------------------------------
     * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
     *
     */

    public static function imageURL($source, $sequence = 1, $fallback = null) {
        $images = self::imagesURL($source, array());
        return isset($images[$sequence - 1]) ? $images[$sequence - 1] : (is_null($fallback) ? Image::placeholder() : $fallback);
    }

}