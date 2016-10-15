<?php
/******************************************************************************
 * An implementation of dicto (scg.unibe.ch/dicto) in and for PHP.
 * 
 * Copyright (c) 2016 Richard Klees <richard.klees@rwth-aachen.de>
 *
 * This software is licensed under The MIT License. You should have received 
 * a copy of the license along with the code.
 */

namespace Lechimp\Dicto\App;

use Lechimp\Dicto\Indexer\IndexerFactory;
use Lechimp\Dicto\Analysis\AnalyzerFactory;
use Lechimp\Dicto\Analysis\Index;
use Lechimp\Dicto\Indexer\Insert;
use Lechimp\Dicto\Graph;
use Psr\Log\LoggerInterface as Log;

/**
 * The Engine of the App drives the analysis process.
 */
class Engine {
    /**
     * @var Log
     */
    protected $log;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var DBFactory
     */
    protected $db_factory;

    /**
     * @var IndexerFactory
     */
    protected $indexer_factory;

    /**
     * @var AnalyzerFactory
     */
    protected $analyzer_factory;

    /**
     * @var SourceStatus
     */
    protected $source_status;

    public function __construct( Log $log
                               , Config $config
                               , DBFactory $db_factory
                               , IndexerFactory $indexer_factory
                               , AnalyzerFactory $analyzer_factory
                               , SourceStatus $source_status
                               ) {
        $this->log = $log;
        $this->config = $config;
        $this->db_factory = $db_factory;
        $this->indexer_factory = $indexer_factory;
        $this->analyzer_factory = $analyzer_factory;
        $this->source_status = $source_status;
    }

    /**
     * Run the analysis.
     * 
     * @return null
     */
    public function run() {
        $index_db_path = $this->index_database_path();
        if (!$this->db_factory->index_db_exists($index_db_path)) {
            $index = $this->build_index();
            $this->run_indexing($index);

            if ($this->config->analysis_store_index()) {
                $index_db = $this->db_factory->build_index_db($index_db_path);
                $this->log->notice("Writing index to database '$index_db_path'...");
                $this->write_index_to($index, $index_db);
            }
        }
        else {
            $index_db = $this->db_factory->load_index_db($index_db_path);
            $this->log->notice("Reading index from database '$index_db_path'...");
            $index = $this->read_index_from($index_db);
        }
        $this->run_analysis($index);
    }

    protected function index_database_path() {
        $commit_hash = $this->source_status->commit_hash();
        return $this->config->project_storage()."/$commit_hash.sqlite";
    }

    protected function result_database_path() {
        return $this->config->project_storage()."/results.sqlite";
    }

    protected function run_indexing(Insert $index) {
        $this->log->notice("Starting to build index...");
        $indexer = $this->indexer_factory->build($index);
        $indexer->index_directory
            ( $this->config->project_root()
            , $this->config->analysis_ignore()
            );
    }

    protected function run_analysis(Index $index) {
        $this->log->notice("Running analysis...");
        $commit_hash = $this->source_status->commit_hash();
        //$result_db = $this->db_factory->get_result_db($this->result_database_path());
        //$result_db->begin_new_run($commit_hash);
        //$analyzer = $this->analyzer_factory->build($index, $result_db);
        $gen = new CLIReportGenerator();
        $analyzer = $this->analyzer_factory->build($index, $gen);
        $analyzer->run();
    }

    protected function build_index() {
        return new Graph\IndexDB;
    }

    protected function write_index_to(Graph\IndexDB $index, IndexDB $db) {
        $db->write_index($index);
    }

    protected function read_index_from(IndexDB $db) {
        return $db->read_index();
    }
}
