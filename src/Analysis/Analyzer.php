<?php
/******************************************************************************
 * An implementation of dicto (scg.unibe.ch/dicto) in and for PHP.
 *
 * Copyright (c) 2016, 2015 Richard Klees <richard.klees@rwth-aachen.de>
 *
 * This software is licensed under The MIT License. You should have received
 * a copy of the license along with the code.
 */

namespace Lechimp\Dicto\Analysis;

use Lechimp\Dicto\Rules\Ruleset;
use Lechimp\Dicto\Variables\Variable;
use Psr\Log\LoggerInterface as Log;

/**
 * Performs the actual analysis of a ruleset over a query-object
 * using a specific rules to sql compiler.
 */
class Analyzer {
    /**
     * @var Log
     */
    protected $log;

    /**
     * @var Ruleset
     */
    protected $ruleset;

    /**
     * @var Index
     */
    protected $index;

    /**
     * @var ReportGenerator
     */
    protected $generator;

    public function __construct
                        ( Log $log
                        , Ruleset $ruleset
                        , Index $index
                        , ReportGenerator $generator
                        ) {
        $this->log = $log;
        $this->ruleset = $ruleset;
        $this->index = $index;
        $this->generator = $generator;
    }

    /**
     * Run the analysis.
     *
     * @return  null
     */
    public function run() {
        $this->generator->begin_ruleset($this->ruleset);
        foreach ($this->ruleset->rules() as $rule) {
            $this->log->info("checking: ".$rule->pprint());
            $this->generator->begin_rule($rule);
            $queries = $rule->compile($this->index);
            foreach ($queries as $query) {
                $results = $query->run(["rule" => $rule]);
                foreach ($results as $row) {
                    $this->generator->report_violation($this->build_violation($row));
                }
            }
            $this->generator->end_rule();
        }
        $this->generator->end_ruleset();
    }

    public function build_violation($info) {
        assert('array_key_exists("rule", $info)');
        assert('$info["rule"] instanceof \\Lechimp\\Dicto\\Rules\\Rule');
        assert('array_key_exists("file", $info)');
        assert('is_string($info["file"])');
        assert('array_key_exists("line", $info)');
        assert('is_int($info["line"])');
        assert('array_key_exists("source", $info)');
        assert('is_string($info["source"])');
        return new Violation
            ( $info["rule"]
            , $info["file"]
            , $info["line"]
            , $info["source"]
            );
    }
} 
