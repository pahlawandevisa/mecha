<?php


/**
 * Comment Manager
 * ---------------
 */

Route::accept(array($config->manager->slug . '/comment', $config->manager->slug . '/comment/(:num)'), function($offset = 1) use($config, $speak) {
    if(Guardian::get('status') !== 'pilot') {
        Shield::abort();
    }
    $offset = (int) $offset;
    File::write($config->total_comments_backend)->saveTo(LOG . DS . 'comments.total.log', 0600);
    if($files = Get::comments('DESC', "", 'txt,hold')) {
        $comments = array();
        $comments_id = array();
        foreach($files as $file) {
            $parts = explode('_', File::B($file));
            $comments_id[] = $parts[1];
        }
        rsort($comments_id);
        foreach(Mecha::eat($comments_id)->chunk($offset, $config->manager->per_page)->vomit() as $comment) {
            $comments[] = Get::comment($comment);
        }
        unset($comments_id, $files);
    } else {
        $comments = false;
    }
    Config::set(array(
        'page_title' => $speak->comments . $config->title_separator . $config->manager->title,
        'pages' => $comments,
        'offset' => $offset,
        'pagination' => Navigator::extract(Get::comments('DESC', "", 'txt,hold'), $offset, $config->manager->per_page, $config->manager->slug . '/comment'),
        'cargo' => 'cargo.response.php'
    ));
    Shield::lot(array('segment' => array('comment', 'article')))->attach('manager');
});


/**
 * Comment Repair
 * --------------
 */

Route::accept($config->manager->slug . '/comment/repair/id:(:num)', function($id = "") use($config, $speak) {
    if(Guardian::get('status') !== 'pilot' || ! $comment = Get::comment($id)) {
        Shield::abort();
    }
    if( ! isset($comment->content_type)) $comment->content_type = $config->html_parser;
    File::write($config->total_comments_backend)->saveTo(LOG . DS . 'comments.total.log', 0600);
    $G = array('data' => Mecha::A($comment));
    Config::set(array(
        'page_title' => $speak->editing . ': ' . $speak->comment . $config->title_separator . $config->manager->title,
        'page' => $comment,
        'html_parser' => $comment->content_type,
        'cargo' => 'repair.response.php'
    ));
    if($request = Request::post()) {
        $request['id'] = $id;
        $request['ua'] = isset($comment->ua) ? $comment->ua : 'N/A';
        $request['ip'] = isset($comment->ip) ? $comment->ip : 'N/A';
        $request['message_raw'] = $request['message'];
        $extension = $request['action'] === 'publish' ? '.txt' : '.hold';
        Guardian::checkToken($request['token']);
        // Empty name field
        if(trim($request['name']) === "") {
            Notify::error(Config::speak('notify_error_empty_field', $speak->comment_name));
            Guardian::memorize($request);
        }
        // Invalid email address
        if(trim($request['email']) !== "" && ! Guardian::check($request['email'], '->email')) {
            Notify::error($speak->notify_invalid_email);
            Guardian::memorize($request);
        }
        $P = array('data' => $request);
        if( ! Notify::errors()) {
            $name = $request['name'];
            $email = Text::parse($request['email'], '->broken_entity');
            $url = isset($request['url']) && trim($request['url']) !== "" ? $request['url'] : false;
            $message = $request['message'];
            $field = Request::post('fields', array());
            include __DIR__ . DS . 'task.field.2.php';
            include __DIR__ . DS . 'task.field.1.php';
            // Update data
            Page::open($comment->path)->header(array(
                'Name' => $name,
                'Email' => $email,
                'URL' => $url,
                'Status' => $request['status'],
                'Content Type' => Request::post('content_type', 'HTML'),
                'UA' => $request['ua'] !== 'N/A' ? $request['ua'] : false,
                'IP' => $request['ip'] !== 'N/A' ? $request['ip'] : false,
                'Fields' => ! empty($field) ? Text::parse($field, '->encoded_json') : false
            ))->content($message)->save();
            File::open($comment->path)->renameTo(File::N($comment->path) . $extension);
            Notify::success(Config::speak('notify_success_updated', $speak->comment));
            Weapon::fire(array('on_comment_update', 'on_comment_repair'), array($G, $P));
            Guardian::kick($config->manager->slug . '/comment/repair/id:' . $id);
        }
    }
    Shield::lot(array('segment' => array('comment', 'article')))->attach('manager');
});


/**
 * Comment Killer
 * --------------
 */

Route::accept($config->manager->slug . '/comment/kill/id:(:num)', function($id = "") use($config, $speak) {
    if(Guardian::get('status') !== 'pilot') {
        Shield::abort();
    }
    if( ! $comment = Get::comment($id)) {
        Shield::abort(); // File not found!
    }
    Config::set(array(
        'page_title' => $speak->deleting . ': ' . $speak->comment . $config->title_separator . $config->manager->title,
        'page' => $comment,
        'cargo' => 'kill.response.php'
    ));
    if($request = Request::post()) {
        $P = array('data' => Mecha::A($comment));
        Guardian::checkToken($request['token']);
        File::open($comment->path)->delete();
        $task_connect = $comment;
        include __DIR__ . DS . 'task.field.3.php';
        File::write($config->total_comments_backend - 1)->saveTo(LOG . DS . 'comments.total.log', 0600);
        Notify::success(Config::speak('notify_success_deleted', $speak->comment));
        Weapon::fire(array('on_comment_update', 'on_comment_destruct'), array($P, $P));
        Guardian::kick($config->manager->slug . '/comment');
    } else {
        File::write($config->total_comments_backend)->saveTo(LOG . DS . 'comments.total.log', 0600);
        Notify::warning($speak->notify_confirm_delete);
    }
    Shield::lot(array('segment' => array('comment', 'article')))->attach('manager');
});