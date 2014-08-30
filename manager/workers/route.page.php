<?php


/**
 * Page Manager
 * ------------
 */

Route::accept(array($config->manager->slug . '/page', $config->manager->slug . '/page/(:num)'), function($offset = 1) use($config, $speak) {
    $pages = false;
    if($files = Mecha::eat(Get::pages('DESC', "", 'txt,draft'))->chunk($offset, $config->per_page)->vomit()) {
        $pages = array();
        foreach($files as $file) {
            $pages[] = Get::pageHeader($file);
        }
    } else {
        if($offset !== 1) Shield::abort();
    }
    Config::set(array(
        'page_title' => $speak->pages . $config->title_separator . $config->manager->title,
        'offset' => $offset,
        'pages' => $pages,
        'pagination' => Navigator::extract(Get::pages('DESC', "", 'txt,draft'), $offset, $config->per_page, $config->manager->slug . '/page'),
        'cargo' => DECK . DS . 'workers' . DS . 'page.php'
    ));
    Shield::attach('manager', false);
});


/**
 * Page Composer/Updater
 * ---------------------
 */

Route::accept(array($config->manager->slug . '/page/ignite', $config->manager->slug . '/page/repair/id:(:num)'), function($id = false) use($config, $speak) {
    Weapon::add('SHIPMENT_REGION_BOTTOM', function() {
        echo Asset::javascript('manager/sword/editor.js');
    });
    Config::set('cargo', DECK . DS . 'workers' . DS . 'repair.page.php');
    if($id && $page = Get::page($id, array('content', 'tags', 'comments'))) {
        if( ! isset($page->fields)) {
            $page->fields = array();
        }
        if( ! File::exist(CUSTOM . DS . date('Y-m-d-H-i-s', $page->date->unix) . '.txt')) {
            $page->css_raw = $config->defaults->page_custom_css;
            $page->js_raw = $config->defaults->page_custom_js;
        }
        Config::set('page_title', $speak->editing . ': ' . $page->title . $config->title_separator . $config->manager->title);
    } else {
        if($id !== false) {
            Shield::abort(); // File not found!
        }
        $page = Mecha::O(array(
            'id' => "",
            'path' => "",
            'state' => 'draft',
            'date' => array('W3C' => ""),
            'title' => $config->defaults->page_title,
            'slug' => "",
            'content_raw' => $config->defaults->page_content,
            'description' => "",
            'author' => Guardian::get('author'),
            'css_raw' => $config->defaults->page_custom_css,
            'js_raw' => $config->defaults->page_custom_js,
            'fields' => array()
        ));
        Config::set('page_title', Config::speak('manager.title_new_', array($speak->page)) . $config->title_separator . $config->manager->title);
    }
    $G = array('data' => Mecha::A($page));
    if($request = Request::post()) {
        Guardian::checkToken($request['token']);
        $request['id'] = (int) date('U', isset($request['date']) ? strtotime($request['date']) : time());
        $request['path'] = $page->path;
        $request['state'] = $request['action'] == 'publish' ? 'published' : 'draft';
        $extension = $request['action'] == 'publish' ? '.txt' : '.draft';
        // Collect all available slug to prevent duplicate
        $slugs = array();
        if($files = Get::pages('DESC', "", 'txt,draft')) {
            foreach($files as $file) {
                list($_time, $_kind, $_slug) = explode('_', basename($file, '.' . pathinfo($file, PATHINFO_EXTENSION)));
                $slugs[$_slug] = 1;
            }
        }
        $slugs[$config->index->slug] = 1;
        $slugs[$config->tag->slug] = 1;
        $slugs[$config->archive->slug] = 1;
        $slugs[$config->search->slug] = 1;
        $slugs[$config->manager->slug] = 1;
        // Set post date by submitted time, or by input value if available
        $date = date('c', $request['id']);
        // General fields
        $title = trim(strip_tags(Request::post('title', $speak->untitled . ' ' . Date::format($date, 'Y/m/d H:i:s')), '<code>,<em>,<i>,<span>'));
        $slug = Text::parse(Request::post('slug', $speak->untitled . '-' . Date::format($date, 'Y-m-d-H-i-s')))->to_slug;
        $content = Request::post('content', "");
        $description = $request['description'];
        $author = strip_tags($request['author']);
        $css = trim(Request::post('css', ""));
        $js = trim(Request::post('js', ""));
        $field = Request::post('fields', array());
        // Checks for invalid time pattern
        if( ! preg_match('#^[0-9]{4,}\-[0-9]{2}\-[0-9]{2}T[0-9]{2}\:[0-9]{2}\:[0-9]{2}\+[0-9]{2}\:[0-9]{2}$#', $date)) {
            Notify::error($speak->notify_invalid_time_pattern);
            Guardian::memorize($request);
        }
        // Checks for duplicate slug
        if( ! $id && isset($slugs[$slug])) {
            Notify::error(Config::speak('notify_error_slug_exist', array($slug)));
            Guardian::memorize($request);
        }
        // Slug must contains at least one letter or one `-`
        if( ! preg_match('#[a-z\-]#i', $slug)) {
            Notify::error($speak->notify_error_slug_missing_letter);
            Guardian::memorize($request);
        }
        // Checks for empty post content
        if(trim($content) === "") {
            Notify::error($speak->notify_error_post_content_empty);
            Guardian::memorize($request);
        }
        $data  = 'Title: ' . $title . "\n";
        $data .= trim($description) !== "" ? 'Description: ' . trim(Text::parse($description)->to_encoded_json) . "\n" : "";
        $data .= 'Author: ' . $author . "\n";
        $data .= ! empty($field) ? 'Fields: ' . Text::parse($field)->to_encoded_json . "\n" : "";
        $data .= "\n" . SEPARATOR . "\n\n" . $content;
        $P = array('data' => $request, 'action' => $request['action']);
        // New
        if( ! $id) {
            if( ! Notify::errors()) {
                File::write($data)->saveTo(PAGE . DS . Date::format($date, 'Y-m-d-H-i-s') . '_0_' . $slug . $extension, 0600);
                if(( ! empty($css) && $css != $config->defaults->page_custom_css) || ( ! empty($js) && $js != $config->defaults->page_custom_js)) {
                    File::write($css . "\n\n" . SEPARATOR . "\n\n" . $js)->saveTo(CUSTOM . DS . Date::format($date, 'Y-m-d-H-i-s') . $extension, 0600);
                }
                Notify::success(Config::speak('notify_success_created', array($title)) . ($extension == '.txt' ? ' <a class="pull-right" href="' . $config->url . '/' . $slug . '" target="_blank"><i class="fa fa-eye"></i> ' . $speak->view . '</a>' : ""));
                Weapon::fire('on_page_update', array($G, $P));
                Weapon::fire('on_page_construct', array($G, $P));
                Guardian::kick($config->manager->slug . '/page/repair/id:' . Date::format($date, 'U'));
            }
        // Repair
        } else {
            // Checks for duplicate slug, except for the current old slug.
            // Allow users to change their post slug, but make sure they
            // do not type the slug of another post.
            unset($slugs[$page->slug]);
            if(isset($slugs[$slug])) {
                Notify::error(Config::speak('notify_error_slug_exist', array($slug)));
                Guardian::memorize($request);
            }
            // Start rewriting ...
            if( ! Notify::errors()) {
                File::open($page->path)->write($data)->save(0600)->renameTo(Date::format($date, 'Y-m-d-H-i-s') . '_0_' . $slug . $extension);
                $custom = CUSTOM . DS . Date::format($page->date->W3C, 'Y-m-d-H-i-s') . $extension;
                if(File::exist($custom)) {
                    if(trim(File::open($custom)->read()) === "" || trim(File::open($custom)->read()) === SEPARATOR || (empty($css) && empty($js)) || ($css == $config->defaults->page_custom_css && $js == $config->defaults->page_custom_js)) {
                        // Always delete empty custom CSS and JavaScript files ...
                        File::open($custom)->delete();
                    } else {
                        File::open($custom)->write($css . "\n\n" . SEPARATOR . "\n\n" . $js)->save(0600)->renameTo(Date::format($date, 'Y-m-d-H-i-s') . $extension);
                    }
                } else {
                    if(( ! empty($css) && $css != $config->defaults->page_custom_css) || ( ! empty($js) && $js != $config->defaults->page_custom_js)) {
                        File::write($css . "\n\n" . SEPARATOR . "\n\n" . $js)->saveTo(CUSTOM . DS . Date::format($date, 'Y-m-d-H-i-s') . $extension, 0600);
                    }
                }
                if($page->slug != $slug && $php_file = File::exist(dirname($page->path) . DS . $page->slug . '.php')) {
                    File::open($php_file)->renameTo($slug . '.php');
                }
                Notify::success(Config::speak('notify_success_updated', array($title)) . ($extension == '.txt' ? ' <a class="pull-right" href="' . $config->url . '/' . $slug . '" target="_blank"><i class="fa fa-eye"></i> ' . $speak->view . '</a>' : ""));
                Weapon::fire('on_page_update', array($G, $P));
                Weapon::fire('on_page_repair', array($G, $P));
                Guardian::kick($config->manager->slug . '/page/repair/id:' . Date::format($date, 'U'));
            }
        }
    }
    Shield::define('default', $page)->attach('manager', false);
});


/**
 * Page Killer
 * -----------
 */

Route::accept($config->manager->slug . '/page/kill/id:(:num)', function($id = "") use($config, $speak) {
    if(Guardian::get('status') != 'pilot' || ! $page = Get::page($id, array('comments'))) {
        Shield::abort();
    }
    Config::set(array(
        'page_title' => $speak->deleting . ': ' . $page->title . $config->title_separator . $config->manager->title,
        'page' => $page,
        'cargo' => DECK . DS . 'workers' . DS . 'kill.page.php'
    ));
    $G = array('data' => Mecha::A($page));
    if($request = Request::post()) {
        Guardian::checkToken($request['token']);
        File::open($page->path)->delete();
        // Deleting custom CSS and JavaScript file of page ...
        File::open(CUSTOM . DS . Date::format($id, 'Y-m-d-H-i-s') . '.txt')->delete();
        File::open(CUSTOM . DS . Date::format($id, 'Y-m-d-H-i-s') . '.draft')->delete();
        // Deleting custom PHP file of page ...
        File::open(dirname($page->path) . DS . $page->slug . '.php')->delete();
        Notify::success(Config::speak('notify_success_deleted', array($page->title)));
        Weapon::fire('on_page_update', array($G, $G));
        Weapon::fire('on_page_destruct', array($G, $G));
        Guardian::kick($config->manager->slug . '/page');
    } else {
        Notify::warning($speak->notify_confirm_delete);
        Notify::warning(Config::speak('notify_confirm_delete_page', array(strtolower($speak->page))));
    }
    Shield::attach('manager', false);
});