<?php
/******************************************************************************
 * An implementation of dicto (scg.unibe.ch/dicto) in and for PHP.
 *
 * Copyright (c) 2016, 2015 Richard Klees <richard.klees@rwth-aachen.de>
 *
 * This software is licensed under The MIT License. You should have received
 * a copy of the license along with the code.
 */

use Lechimp\Dicto as Dicto;
use Lechimp\Dicto\Rules as R;
use Lechimp\Dicto\Variables as V;
use Lechimp\Dicto\Graph\IndexDB;
use Lechimp\Dicto\Analysis\Violation;
use Psr\Log\LogLevel;
use Lechimp\Dicto\Indexer\ASTVisitor;
use Lechimp\Dicto\Indexer\Insert;
use Lechimp\Dicto\Indexer\Indexer;
use PhpParser\ParserFactory;

require_once(__DIR__."/LoggerMock.php");
require_once(__DIR__."/ReportGeneratorMock.php");
require_once(__DIR__."/IndexerExpectations.php");

abstract class RuleTest extends PHPUnit_Framework_TestCase {
    use IndexerExpectations;

    /**
     * @return  R\Schema
     */
    abstract public function schema();

    protected function indexer(Insert $insert_mock) {
        $lexer = new \PhpParser\Lexer\Emulative
            (["usedAttributes" => ["comments", "startLine", "endLine", "startFilePos"]]);
        $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7, $lexer);
        $logger_mock = new LoggerMock();
        $schema = $this->schema();
        $indexer = new Indexer
            ( $logger_mock
            , $parser
            , $insert_mock
            , $schema instanceof ASTVisitor ? [$schema] : []
            );
        return $indexer;
    }

    public function setUp() {
        $this->db = new IndexDB();

        $this->rp = new ReportGeneratorMock();
        $this->log = new LoggerMock();
        $lexer = new \PhpParser\Lexer\Emulative
            (["usedAttributes" => ["comments", "startLine", "endLine", "startFilePos"]]);
        $this->parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7, $lexer);
        $schema = $this->schema();
        $this->indexer = new Indexer
            ( $this->log
            , $this->parser
            , $this->db
            , $schema instanceof ASTVisitor ? [$schema] : []
            );
    }

    public function analyze(R\Rule $rule, $source) {
        $this->indexer->index_content("source.php", $source);

        $ruleset = new R\Ruleset($rule->variables(), array($rule));
        $analyzer = new Dicto\Analysis\Analyzer($this->log, $ruleset, $this->db, $this->rp);
        $analyzer->run();
        return $this->rp->violations;
    }
}
