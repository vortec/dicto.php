<?php
/******************************************************************************
 * An implementation of dicto (scg.unibe.ch/dicto) in and for PHP.
 *
 * Copyright (c) 2016, 2015 Richard Klees <richard.klees@rwth-aachen.de>
 *
 * This software is licensed under The MIT License. You should have received
 * a copy of the license along with the code.
 */

use Lechimp\Dicto\Variables\Variable;
use Lechimp\Dicto\Graph\IndexDB;
use Lechimp\Dicto\Graph\Query;
use Lechimp\Dicto\Graph\Node;
use Lechimp\Dicto\Graph\Relation;

class GraphIndexDBTest extends PHPUnit_Framework_TestCase {
    public function setUp() {
        $this->db = new IndexDB();
    }

    public function test_insert_source() {
        $this->db->source("foo.php", "FOO\nBAR");

        $res = (new Query)
            ->with_condition(function(Node $n) {
                return $n->type() == "file";
            })
            ->with_condition(function(Relation $r) {
                return $r->type() == "contains";
            })
            ->with_condition(function(Node $n) {
                return $n->type() == "line";
            })
            ->execute_on($this->db)
            ->extract(function($f,$_, $l) {
                return
                    [ "path" => $f->property("path")
                    , "line" => $l->property("num")
                    , "source" => $l->property("source")
                    ];
            });
        $expected = array
            ( array
                ( "path" => "foo.php"
                , "line" => "1"
                , "source" => "FOO"
                )
            , array
                ( "path" => "foo.php"
                , "line" => "2"
                , "source" => "BAR"
                )
            );
        $this->assertEquals($expected, $res);
    }

/*    public function test_insert_definition() {
        $this->db->source("AClass.php", "FOO\nBAR");
        $this->db->definition("AClass", Variable::CLASS_TYPE, "AClass.php", 1, 2);

        $builder = $this->builder();
        $b = $builder->expr();
        $res = $builder
            ->select
                ( "n.name"
                , "n.type"
                , "f.path"
                , "d.start_line"
                , "d.end_line"
                )
            ->from($this->db->definition_table(), "d")
            ->join
                ( "d", $this->db->name_table(), "n"
                , $b->eq("d.name", "n.id")
                )
            ->join
                ( "d", $this->db->file_table(), "f"
                , $b->eq("d.file", "f.id")
                )
            ->execute()
            ->fetchAll();
        $expected = array
            ( "name" => "AClass"
            , "type" => Variable::CLASS_TYPE
            , "path" => "AClass.php"
            , "start_line" => "1"
            , "end_line" => "2"
            );
        $this->assertEquals(array($expected), $res);
    }

    public function test_insert_name() {
        $id = $this->db->name("AClass", Variable::CLASS_TYPE);

        $builder = $this->builder();
        $b = $builder->expr();
        $res = $builder
            ->select
                ( "n.id"
                , "n.name"
                , "n.type"
                )
            ->from($this->db->name_table(), "n")
            ->execute()
            ->fetchAll();
        $expected = array
            ( "id" => $id
            , "name" => "AClass"
            , "type" => Variable::CLASS_TYPE
            );
        $this->assertEquals(array($expected), $res);
    }

    public function test_insert_some_relation() {
        $this->db->source("AClass.php", "FOO\nBAR");
        $this->db->source("BClass.php", "FOO\nBAR");
        $id1 = $this->db->name("AClass", Variable::CLASS_TYPE);
        $id2 = $this->db->name("BClass", Variable::CLASS_TYPE);
        $this->db->relation($id1, $id2, "some_relation", "BClass.php", 1);

        $builder = $this->builder();
        $b = $builder->expr();
        $res = $builder
            ->select
                ( "r.name_left"
                , "r.name_right"
                , "r.which"
                , "f.path"
                , "r.line"
                )
            ->from($this->db->relation_table(), "r")
            ->join
                ( "r", $this->db->file_table(), "f"
                , $b->eq("r.file", "f.id")
                )
            ->execute()
            ->fetchAll();
        $expected = array
            ( "name_left" => "$id1"
            , "name_right" => "$id2"
            , "which" => "some_relation"
            , "path" => "BClass.php"
            , "line" => "1"
            );

        $this->assertEquals(array($expected), $res);
    }

    public function test_retreive_name_id() {
        $id1 = $this->db->name("AClass", Variable::CLASS_TYPE);
        $id2 = $this->db->name("AClass", Variable::CLASS_TYPE);
        $this->assertInternalType("integer", $id1);
        $this->assertEquals($id1, $id2);
    }

    public function test_retreive_file_id() {
        $id1 = $this->db->file("AClass.php");
        $id2 = $this->db->file("AClass.php");
        $this->assertInternalType("integer", $id1);
        $this->assertEquals($id1, $id2);
    }

    public function test_retreive_name_of_definition() {
        $this->db->source("AClass.php", "FOO\nBAR");
        list($id1,$_) = $this->db->definition("AClass", Variable::CLASS_TYPE, "AClass.php", 1, 2);
        $id2 = $this->db->name("AClass", Variable::CLASS_TYPE);
        $this->assertEquals($id1, $id2);
    }

    public function test_retreive_file_of_source() {
        $id1 = $this->db->source("AClass.php", "");
        $id2 = $this->db->file("AClass.php");
        $this->assertInternalType("integer", $id1);
        $this->assertEquals($id1, $id2);
    }

    public function test_insert_two_method_definitions() {
        $this->db->source("AClass.php", "FOO\nBAR");
        $this->db->source("BClass.php", "FOO\nBAR");
        $this->db->definition("a_method", Variable::METHOD_TYPE, "AClass.php", 1, 2);
        $this->db->definition("a_method", Variable::METHOD_TYPE, "BClass.php", 1, 2);

        $builder = $this->builder();
        $b = $builder->expr();
        $res = $builder
            ->select
                ( "n.name"
                , "n.type"
                , "f.path"
                , "d.start_line"
                , "d.end_line"
                )
            ->from($this->db->definition_table(), "d")
            ->join
                ( "d", $this->db->name_table(), "n"
                , $b->eq("d.name", "n.id")
                )
            ->join
                ( "d", $this->db->file_table(), "f"
                , $b->eq("d.file", "f.id")
                )
            ->execute()
            ->fetchAll();
        $expected = array
            ( array
                ( "name" => "a_method"
                , "type" => Variable::METHOD_TYPE
                , "path" => "AClass.php"
                , "start_line" => "1"
                , "end_line" => "2"
                )
            , array
                ( "name" => "a_method"
                , "type" => Variable::METHOD_TYPE
                , "path" => "BClass.php"
                , "start_line" => "1"
                , "end_line" => "2"
                )
            );
        $this->assertEquals($expected, $res);
    }

    public function test_insert_two_relations() {
        $this->db->source("AClass.php", "FOO\nBAR");
        list($id1,$_) = $this->db->definition("AClass", Variable::CLASS_TYPE, "AClass.php", 1, 2);
        $id2 = $this->db->name("AClass", Variable::CLASS_TYPE);
        $this->db->relation($id1, $id2, "some_relation", "AClass.php", 1);
        $this->db->relation($id1, $id2, "some_relation", "AClass.php", 2);

        $builder = $this->builder();
        $b = $builder->expr();
        $res = $builder
            ->select
                ( "r.name_left"
                , "r.name_right"
                , "r.which"
                , "f.path"
                , "r.line"
                )
            ->from($this->db->relation_table(), "r")
            ->join
                ( "r", $this->db->file_table(), "f"
                , $b->eq("r.file", "f.id")
                )
            ->execute()
            ->fetchAll();
        $expected = array
            ( array
                ( "name_left" => "$id1"
                , "name_right" => "$id2"
                , "which" => "some_relation"
                , "path" => "AClass.php"
                , "line" => "1"
                )
            , array
                ( "name_left" => "$id1"
                , "name_right" => "$id2"
                , "which" => "some_relation"
                , "path" => "AClass.php"
                , "line" => "2"
                )
            );

        $this->assertEquals($expected, $res);
    }

    public function test_insert_two_relations_same_line() {
        $this->db->source("AClass.php", "FOO\nBAR");
        list($id1,$_) = $this->db->definition("AClass", Variable::CLASS_TYPE, "AClass.php", 1, 2);
        $id2 = $this->db->name("AClass", Variable::CLASS_TYPE);
        $this->db->relation($id1, $id2, "some_relation", "AClass.php", 1);
        $this->db->relation($id1, $id2, "some_relation", "AClass.php", 1);

        $builder = $this->builder();
        $b = $builder->expr();
        $res = $builder
            ->select
                ( "r.name_left"
                , "r.name_right"
                , "r.which"
                , "f.path"
                , "r.line"
                )
            ->from($this->db->relation_table(), "r")
            ->join
                ( "r", $this->db->file_table(), "f"
                , $b->eq("r.file", "f.id")
                )
            ->execute()
            ->fetchAll();
        $expected = array
            ( array
                ( "name_left" => "$id1"
                , "name_right" => "$id2"
                , "which" => "some_relation"
                , "path" => "AClass.php"
                , "line" => "1"
                )
            , array
                ( "name_left" => "$id1"
                , "name_right" => "$id2"
                , "which" => "some_relation"
                , "path" => "AClass.php"
                , "line" => "1"
                )
            );

        $this->assertEquals($expected, $res);
    }

    public function test_method_info() {
        $this->db->source("AClass.php", "FOO\nBAR");
        list($cls_id, $_) = $this->db->definition("AClass", Variable::CLASS_TYPE, "AClass.php", 1, 2);
        list($mtd_id, $def_id) = $this->db->definition("a_method", Variable::METHOD_TYPE, "AClass.php", 1, 2);
        $this->db->method_info($mtd_id, $cls_id, $def_id);

        $builder = $this->builder();
        $b = $builder->expr();
        $res = $builder
            ->select
                ( "name"
                , "class"
                , "definition"
                )
            ->from($this->db->method_info_table(), "d")
            ->execute()
            ->fetchAll();
        $expected = array
            ( array
                ( "name"        => $mtd_id
                , "class"       => $cls_id
                , "definition"  => $def_id
                )
            );
        $this->assertEquals($expected, $res);
    }*/
}