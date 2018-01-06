<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use GuzzleHttp\Client;
use File;

class GenerateSitemap extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:sitemap';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates Sitemap';

    protected $client;
    protected $urlArray = array();
    public $sCounter;
    public $host;

    /**
     * Create a new command instance.
     * @param GuzzleHttp\Client $client
     * @return void
     */
    public function __construct(Client $client) {
        parent::__construct();
        $this->host = "<domain-name>";
        $this->client = $client;
        $this->urlArray = array();
        $this->sCounter = 1;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle() {
        $urlArray = $this->urlArray;
        $urlArray = $this->addRoutes($urlArray);
        $this->callAPIs($urlArray);
    }

    /**
     * Adds Routes from routes/web.php, excludes some filtered urls
     * @param $urlArray
     * @return $urlArray
     */
    private function addRoutes($urlArray) {
        $routeCollection = Route::getRoutes();
        $urlArray[] = $this->host;
        foreach ($routeCollection as $value) {
           if((!strrpos($value->uri(), 'vue_capture')) && (!strrpos($value->uri(), 'log-off'))
            && (!strrpos($value->uri(), '/')) && ($value->uri() != '/'))
                $urlArray[] = $this->host.$value->uri();
        }
        return $urlArray;
    }

    /**
     * Calls API and build Sitemap Index
     * @param $urlArray
     * @return void
     */
    private function callAPIs($urlArray) {
        $urlArray = $this->fetchRows($urlArray);
        $this->buildSitemapIndex($this->sCounter);
    }

    /**
     * Call API for fetching
     * @param $urlArray
     * @return void
     */
    private function fetchRows($urlArray) {
        $apiURL = "<api-end-point>";
        $this->arrayBuilder($apiURL, $urlArray);
    }

    /**
     * Builds urlArray and builds a sitemap page for every 500 url's
     * @param $apiURL
     * @param $urlArray
     * @param $type
     * @return void
     */
    private function arrayBuilder($apiURL, $urlArray, $type = "") {
        $response = $this->client->get($apiURL);
        $response_body = json_decode(utf8_encode($response->getBody()->getContents()));
        $page_count = $response_body->total_pages;

        if ($page_count > 1) {
            $recCount = $response_body->page_size;
        } else {
            $recCount = $response_body->total_count - $response_body->page_size;
        }

        for($ipage = 2; $ipage <= $page_count; $ipage++) {
            if($ipage%10 == 0) {
                $this->buildSitemapPages($urlArray, $this->sCounter);
            }
            $iresponse = $this->client->get($apiURL.$pageString.$ipage);
            $iresponse_body = json_decode(utf8_encode($iresponse->getBody()->getContents()));
            $irecCount = $response_body->total_count - ($response_body->page_size * ($ipage-1));
            if ($irecCount < $response_body->page_size)
                $iprecCounter = $irecCount;
            else
                $iprecCounter = $response_body->page_size;
            for ($iprec = 0; $iprec < $iprecCounter; $iprec++) {
                $urlArray[] = $iresponse_body->items[$iprec]->url;
            }
        }
        if(isset($urlArray)) {
            $this->buildSitemapPages($urlArray, $this->sCounter);
        }
    }

    /**
     * Builds Sitemap map pages
     * @param $urlArray
     * @param $sCounter
     * @return void
     */
    private function buildSitemapPages($urlArray, $sCounter) {
        $counter = 500;
        $offset = 0;
        for ($size = $counter, $urlTotalCount = count($urlArray); ($urlTotalCount+$counter)/$size > 1; $size = $size+$counter) {
            $sUrlArray = array_slice($urlArray, $offset, $counter);
            $offset = $offset + $counter;
            File::put(public_path().'/sitemap-'.$sCounter.'.xml', view('layouts.sitemap')->with('urlArray', $sUrlArray)->render());
        }
        unset($urlArray);
        $this->sCounter = $this->sCounter+1;
    }

    /**
     * Builds Sitemap Index page
     * @param $sCounter
     * @return void
     */
    private function buildSitemapIndex($counter = 1) {
        File::put(public_path().'/sitemap.xml', view('layouts.sitemap-index')->with('sCounter', $counter));
    }
}
