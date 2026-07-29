<?php


namespace core\admin\controllers;


use core\base\controllers\BaseMethods;

class CreatesitemapController extends BaseAdmin
{
    use BaseMethods;

    protected $all_links = [];
    protected $temp_links = [];
    protected $bad_links = [];

    protected $maxLinks = 500;

    protected $parsingLogFile = 'parsing_log.txt';
    protected $fileArr = ['jpg', 'png', 'jpeg', 'gif', 'xls', 'xlsx', 'pdf', 'mp4', 'avi', 'mp3'];
    protected $filterArr = [
        'url' => ['order', 'page'],
        'get' => []
    ];

    public function inputData($links_counter = 1, $redirect=true){

        $links_counter = $this->clearNum($links_counter);

        if(!function_exists('curl_init')){

            $this->cancel(0, 'Library CURL as not find. Creation Sitemap imposible', '', true);

        }

        if(!$this->userId) $this->execBase();
        if(!$this->checkParsingTable()){
            $this->cancel(0, 'You have problem with DATA BASE TABLE - parsing_data', '', true);
        };

        set_time_limit(0);
        $reserve = $this->model->get('parsing_data')[0];

        $table_rows=[];

        foreach ($reserve as $name=>$item){

            $table_rows[$name] = '';

            if($item) $this->$name = json_decode($item);
                elseif($name === 'all_links' || $name === 'temp_links') $this->$name = [SITE_URL];
        }
        $this->maxLinks = (int)$links_counter > 1 ? ceil($this->maxLinks / $links_counter) : $this->maxLinks;

        while ($this->temp_links){
            $temp_links_count = count($this->temp_links);
            $links = $this->temp_links;
            $this->temp_links = [];

            if($temp_links_count > $this->maxLinks){
                $links = array_chunk($links, ceil($temp_links_count / $this->maxLinks));

                $count_chunks = count($links);

                for($i=0; $i<= $count_chunks; $i++){
                    $this->parsing($links[$i]);
                    unset($links[$i]);

                    if($links){

                        foreach ($table_rows as $name => $item){
                            if($name === 'temp_links') $table_rows[$name] = json_encode(array_merge(...$links));
                                 else $table_rows[$name] = json_encode($this->$name);
                        }

                        $this->model->edit('parsing_data',[
                            'fields' => $table_rows
                        ]);
                    }
                }
            }else{
                $this->parsing($links);
            }

            foreach ($table_rows as $name => $item){
                $table_rows[$name] = json_encode($this->$name);
            }

            $this->model->edit('parsing_data',[
                'fields' => $table_rows
            ]);
        }

        foreach ($table_rows as $name => $item){
            $table_rows[$name] = '';
        }


        $this->model->edit('parsing_data',[
            'fields' => $table_rows
        ]);

        if($this->all_links){
            foreach ($this->all_links as $key=>$link){
                if(!$this->filter($link) || in_array($link, $this->bad_links)) unset($this->all_links[$key]);

            }
        }

            $this->createSitemap();

         if($redirect){
             !$_SESSION['res']['answer'] && $_SESSION['res']['answer'] = '<div class="success">Sitemap is created</div>';

             $this->redirect();
         }else{
             $this->cancel(1, 'Sitemap is created! ' . count($this->all_links) . 'links', '', true);
         }
    }

    protected function parsing($urls){

//        $urls = (array)$urls;
        if(!$urls) return;

        $curlMulti = curl_multi_init();
        $curl = [];

        foreach ($urls as $i=>$url) {
            $curl [$i]= curl_init();
            curl_setopt($curl[$i], CURLOPT_URL, $url);
            curl_setopt($curl[$i], CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl[$i], CURLOPT_HEADER, true);
            curl_setopt($curl[$i], CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($curl[$i], CURLOPT_TIMEOUT, 120);
            curl_setopt($curl[$i], CURLOPT_ENCODING, 'gzip,deflate');

            curl_multi_add_handle($curlMulti, $curl[$i]);
        }

        do{
            $status = curl_multi_exec($curlMulti, $active);
            $info = curl_multi_info_read($curlMulti);

            if(false !== $info){

                if($info['result'] !==0){
                    $i = array_search($info['handle'], $curl);

                    $error = curl_errno($curl[$i]);
                    $message = curl_error($curl[$i]);
                    $header = curl_getinfo($curl[$i]);

                    if($error !==0){
                        $this->cancel(0, 'Error loading ' . $header['url'] .
                                        'http code: ' . $header['http_code'] .
                                        'error: ' . $error . 'message' . $message
                                     );
                    }
                }
            }
            if($status>0){
                $this->cancel(0, curl_multi_strerror($status));
            }

        }while($status === CURLM_CALL_MULTI_PERFORM || $active);

            $result = [];
            foreach ($urls as $i=>$url){
                $result[$i] = curl_multi_getcontent($curl[$i]);
                curl_multi_remove_handle($curlMulti, $curl[$i]);
                curl_close($curl[$i]);

                    if(!preg_match('/Content-Type:\s+text\/html/ui', $result[$i])) {

                        $this->bad_links[] = $url;

                       $this->cancel(0, 'Incorrect content type - ' . $url);
                       continue;
                    }
                    if(!preg_match('/HTTP\/\d\.?\d?\s+20\d/uis', $result[$i])){

                        $this->bad_links[] = $url;

                        $this->cancel(0, 'Incorrect server code - ' . $url);
                        continue;
                    }

                    $this->createLinks($result[$i]);
            }
            curl_multi_close($curlMulti);
    }

    protected function createLinks($content){
        if($content){

//        $str = ' <a class = "class" id ="1" href ="hfhjsdhfdhow" data-id="jhljfls" >';
//        $str = "<a class = \"class\" id =\"1\" href = \"hfhjsdhfdhow\" data-id=\"jhljfls\" >";
            /*        $ptr = '/<a\s*?[^>]*?href\s*?=(if not use link type [href = ] can be not process, work only [href =])(["\'])(.+?)\1[^>]*?>/ui';*/
            $pattern = '/<a\s*?[^>]*?href\s*?=\s*?(["\'])(.+?)\1[^>]*?>/ui';
            preg_match_all($pattern , $content, $links);

            if($links[2]){

                foreach ($links[2] as $link) {
                    if ($link === '/' || $link === SITE_URL . '/') continue;

                    foreach ($this->fileArr as $ext) {
                        if ($ext) {
                            $ext = addslashes($ext);
                            $ext = str_replace('.', '\.', $ext);

                            if (preg_match('/' . $ext . '(\s*?$|\?[^\/]*$)/ui', $link, $matches)) {
                                continue 2;
                            }
                        }
                    }
                    if(strpos($link, '/')===0){
                        $link = SITE_URL . $link;
                    }

                    $site_url = mb_str_replace('.', '\.', mb_str_replace('/', '\/', SITE_URL));


                    if (!in_array($link, $this->bad_links) &&
                        !preg_match('/^(' . $site_url . ')?\/?#[^\/]*?$/ui', $link) &&
                        strpos($link, SITE_URL)===0 &&
                        !in_array($link, $this->all_links)){

                        $this->temp_links[] = $link;
                        $this->all_links[] = $link;
                    }
                }
            }
        }
    }

    protected function filter($link){
        if($this->filterArr){

            foreach ($this->filterArr as $type => $values){
                if($values){
                    foreach ($values as $item){
                        $item = str_replace('/', '\/', addslashes($item));

                        if($type === 'url'){
                            if(preg_match('/^[^\?]*' . $item . '/ui', $link)){
                                return false;
                            }
                        }
                        if($type ==='get'){
                                if(preg_match('/(\?|&amp;|=|&)'. $item .'(=|&amp;|&|$)/ui', $link, $matches)){
                                    return false;
                                }
                        }
                    }
                }
            }
        }
    return true;
    }

    protected function checkParsingTable(){

        $tables = $this->model->showTables();

        if(!in_array('parsing_data', $tables)){
            $query = 'CREATE TABLE parsing_data (all_links longtext, temp_links longtext, bad_links longtext)';

            if(!$this->model->query($query, 'c') ||
                !$this->model->add('parsing_data', ['fields'=>['all_links' => '', 'temp_links' => '', 'bad_links'=>'']])){
                return false;
            }
        }
        return true;
    }

    protected function cancel($success = 0, $message = '', $log_message = '', $exit = false){
        $exitArr = [];
        $exitArr['success'] = $success;
        $exitArr['message'] = $message ? $message : 'ERROR PARSING';
        $log_message = $log_message ? $log_message : $exitArr['message'];

        $class = 'success';

        if(!$exitArr['success']){
            $class = 'error';
            $this->writeLog($log_message, 'parsing_log.txt');
        }
        if($exit){
            $exitArr['message'] = '<div> class="' .$class. '">' . $exitArr['message'] . '</div>';
            exit(json_encode($exitArr));
        }
    }


    /**
     * Public canonical origin for sitemap output.
     *
     * The crawler may run against a local SITE_URL, while emitted locations
     * always use the accepted public HTTPS origin.
     */
    protected function sitemapCanonicalOrigin(): string
    {
        $configuredOrigin = trim(
            (string)getenv('FP_WEB_CANONICAL_ORIGIN')
        );

        if (
            !preg_match(
                '#^https://[a-z0-9.-]+(?::[0-9]{1,5})?$#D',
                $configuredOrigin
            )
        ) {
            $configuredOrigin = 'https://forprint.net.ua';
        }

        return rtrim(
            strtolower($configuredOrigin),
            '/'
        );
    }

    protected function sitemapCanonicalUrl(string $url): ?string
    {
        $parts = parse_url($url);

        if ($parts === false) {
            return null;
        }

        $path = (string)($parts['path'] ?? '/');
        $path = preg_replace('#/+#', '/', $path) ?: '/';
        $path = '/' . trim($path, '/');

        if ($path === '//') {
            $path = '/';
        }

        $normalizedPath = rtrim($path, '/');

        if ($normalizedPath === '') {
            $normalizedPath = '/';
        }

        $blockedPrefixes = [
            '/admin',
            '/cart',
            '/search',
            '/lk',
            '/login',
            '/logout',
            '/communication-request.php',
            '/search-suggestions.php',
        ];

        foreach ($blockedPrefixes as $blockedPrefix) {
            if (
                $normalizedPath === $blockedPrefix
                || strpos(
                    $normalizedPath,
                    $blockedPrefix . '/'
                ) === 0
            ) {
                return null;
            }
        }

        if (
            $normalizedPath !== '/'
            && !preg_match(
                '/\.[a-z0-9]{1,8}$/i',
                $normalizedPath
            )
        ) {
            $normalizedPath .= '/';
        }

        return $this->sitemapCanonicalOrigin()
            . (
                $normalizedPath === '/'
                    ? '/'
                    : $normalizedPath
            );
    }

    protected function createSitemap()
    {
        $dom = new \DOMDocument(
            '1.0',
            'UTF-8'
        );
        $dom->formatOutput = true;

        $root = $dom->createElement('urlset');
        $root->setAttribute(
            'xmlns',
            'http://www.sitemaps.org/schemas/sitemap/0.9'
        );
        $dom->appendChild($root);

        $canonicalLinks = [];

        foreach ((array)$this->all_links as $item) {
            $canonicalUrl = $this->sitemapCanonicalUrl(
                (string)$item
            );

            if ($canonicalUrl !== null) {
                $canonicalLinks[$canonicalUrl] = true;
            }
        }

        $canonicalUrls = array_keys(
            $canonicalLinks
        );

        usort(
            $canonicalUrls,
            static function (
                string $left,
                string $right
            ): int {
                if ($left === 'https://forprint.net.ua/') {
                    return -1;
                }

                if ($right === 'https://forprint.net.ua/') {
                    return 1;
                }

                return strcmp($left, $right);
            }
        );

        foreach ($canonicalUrls as $canonicalUrl) {
            $urlElement = $dom->createElement('url');
            $locationElement = $dom->createElement('loc');
            $locationElement->appendChild(
                $dom->createTextNode($canonicalUrl)
            );
            $urlElement->appendChild($locationElement);
            $root->appendChild($urlElement);
        }

        $dom->save(
            $_SERVER['DOCUMENT_ROOT']
            . PATH
            . 'sitemap.xml'
        );
    }

}
