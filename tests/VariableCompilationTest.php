<?php
/******************************************************************************
 * An implementation of dicto (scg.unibe.ch/dicto) in and for PHP.
 *
 * Copyright (c) 2016, 2015 Richard Klees <richard.klees@rwth-aachen.de>
 *
 * This software is licensed under The MIT License. You should have received
 * a copy of the license along with the code.
 */

use Lechimp\Dicto\Variables as V;
use Lechimp\Dicto\Graph\IndexDB;

class VariableCompilationTest extends PHPUnit_Framework_TestCase {
    public function setUp() {
        $this->db = new IndexDB();
    }

    public function test_compile_everything() {
        $var = new V\Everything();
        $compiled = $var->compile();

        $f = $this->db->_file("source.php", "A\nB");
        $c = $this->db->_class("AClass", $f, 1,2);
        $m = $this->db->_method("a_method", $c, $f, 1, 2);

        $res = $this->db->query()
            ->filter($compiled)
            ->extract(function($n,&$r) {
                $r[] = $n;
            })
            ->run([]);
        $this->assertEquals([[$f],[$c],[$m]], $res);
    }

    public function test_compile_everything_negated() {
        $var = new V\Everything();
        $compiled = $var->compile(true);

        $f = $this->db->_file("source.php", "A\nB");
        $c = $this->db->_class("AClass", $f, 1,2);
        $m = $this->db->_method("a_method", $c, $f, 1, 2);

        $res = $this->db->query()
            ->filter($compiled)
            ->extract(function($n,&$r) {
                $r[] = $n;
            })
            ->run([]);
        $this->assertEquals([], $res);
    }

    public function test_compile_files() {
        $var = new V\Files();
        $compiled = $var->compile();

        $f = $this->db->_file("source.php", "A\nB");
        $c = $this->db->_class("AClass", $f, 1,2);
        $m = $this->db->_method("a_method", $c, $f, 1, 2);

        $res = $this->db->query()
            ->filter($compiled)
            ->extract(function($n,&$r) {
                $r[] = $n;
            })
            ->run([]);
        $this->assertEquals([[$f]], $res);
    }

    public function test_compile_files_negated() {
        $var = new V\Files();
        $compiled = $var->compile(true);

        $f = $this->db->_file("source.php", "A\nB");
        $c = $this->db->_class("AClass", $f, 1,2);
        $m = $this->db->_method("a_method", $c, $f, 1, 2);

        $res = $this->db->query()
            ->filter($compiled)
            ->extract(function($n,&$r) {
                $r[] = $n;
            })
            ->run([]);
        $this->assertEquals([[$c],[$m]], $res);
    }

    public function test_compile_classes() {
        $var = new V\Classes();
        $compiled = $var->compile();

        $f = $this->db->_file("source.php", "A\nB");
        $c = $this->db->_class("AClass", $f, 1,2);
        $m = $this->db->_method("a_method", $c, $f, 1, 2);

        $res = $this->db->query()
            ->filter($compiled)
            ->extract(function($n,&$r) {
                $r[] = $n;
            })
            ->run([]);
        $this->assertEquals([[$c]], $res);
    }

    public function test_compile_classes_negated() {
        $var = new V\Classes();
        $compiled = $var->compile(true);

        $f = $this->db->_file("source.php", "A\nB");
        $c = $this->db->_class("AClass", $f, 1,2);
        $m = $this->db->_method("a_method", $c, $f, 1, 2);

        $res = $this->db->query()
            ->filter($compiled)
            ->extract(function($n,&$r) {
                $r[] = $n;
            })
            ->run([]);
        $this->assertEquals([[$f],[$m]], $res);
    }

    public function test_compile_methods() {
        $var = new V\Methods();
        $compiled = $var->compile();

        $f = $this->db->_file("source.php", "A\nB");
        $c = $this->db->_class("AClass", $f, 1,2);
        $m1 = $this->db->_method("a_method", $c, $f, 1, 2);
        $m2 = $this->db->_method_reference("another_method", $f, 1);

        $res = $this->db->query()
            ->filter($compiled)
            ->extract(function($n,&$r) {
                $r[] = $n;
            })
            ->run([]);
        $this->assertEquals([[$m1],[$m2]], $res);
    }

    public function test_compile_methods_negated() {
        $var = new V\Methods();
        $compiled = $var->compile(true);

        $f = $this->db->_file("source.php", "A\nB");
        $c = $this->db->_class("AClass", $f, 1,2);
        $m1 = $this->db->_method("a_method", $c, $f, 1, 2);
        $m2 = $this->db->_method_reference("another_method", $f, 1);

        $res = $this->db->query()
            ->filter($compiled)
            ->extract(function($n,&$r) {
                $r[] = $n;
            })
            ->run([]);
        $this->assertEquals([[$f],[$c]], $res);
    }

    public function test_compile_functions() {
        $var = new V\Functions();
        $compiled = $var->compile();

        $f = $this->db->_file("source.php", "A\nB");
        $c = $this->db->_class("AClass", $f, 1,2);
        $f1 = $this->db->_function("a_function", $f, 1, 2);
        $f2 = $this->db->_function_reference("another_function", $f, 1);

        $res = $this->db->query()
            ->filter($compiled)
            ->extract(function($n,&$r) {
                $r[] = $n;
            })
            ->run([]);
        $this->assertEquals([[$f1],[$f2]], $res);
    }

    public function test_compile_functions_negated() {
        $var = new V\Functions();
        $compiled = $var->compile(true);

        $f = $this->db->_file("source.php", "A\nB");
        $c = $this->db->_class("AClass", $f, 1,2);
        $f1 = $this->db->_function("a_function", $f, 1, 2);
        $f2 = $this->db->_function_reference("another_function", $f, 1);

        $res = $this->db->query()
            ->filter($compiled)
            ->extract(function($n,&$r) {
                $r[] = $n;
            })
            ->run([]);
        $this->assertEquals([[$f],[$c]], $res);
    }

    public function test_compile_globals() {
        $var = new V\Globals();
        $compiled = $var->compile();

        $f = $this->db->_file("source.php", "A\nB");
        $c = $this->db->_class("AClass", $f, 1,2);
        $g = $this->db->_global("a_global", $f, 1, 2);

        $res = $this->db->query()
            ->filter($compiled)
            ->extract(function($n,&$r) {
                $r[] = $n;
            })
            ->run([]);
        $this->assertEquals([[$g]], $res);
    }

    public function test_compile_globals_negated() {
        $var = new V\Globals();
        $compiled = $var->compile(true);

        $f = $this->db->_file("source.php", "A\nB");
        $c = $this->db->_class("AClass", $f, 1,2);
        $g = $this->db->_global("a_global", $c, $f, 1, 2);

        $res = $this->db->query()
            ->filter($compiled)
            ->extract(function($n,&$r) {
                $r[] = $n;
            })
            ->run([]);
        $this->assertEquals([[$f],[$c]], $res);
    }

    public function test_compile_language_constructs() {
        $var = new V\LanguageConstruct("die");
        $compiled = $var->compile();

        $f = $this->db->_file("source.php", "A\nB");
        $c = $this->db->_class("AClass", $f, 1,2);
        $l = $this->db->_language_construct("die", $f, 1, 2);

        $res = $this->db->query()
            ->filter($compiled)
            ->extract(function($n,&$r) {
                $r[] = $n;
            })
            ->run([]);
        $this->assertEquals([[$l]], $res);
    }

    public function test_compile_language_constructs_negated() {
        $var = new V\LanguageConstruct("die");
        $compiled = $var->compile(true);

        $f = $this->db->_file("source.php", "A\nB");
        $c = $this->db->_class("AClass", $f, 1,2);
        $l = $this->db->_language_construct("die", $c, $f, 1, 2);

        $res = $this->db->query()
            ->filter($compiled)
            ->extract(function($n,&$r) {
                $r[] = $n;
            })
            ->run([]);
        $this->assertEquals([[$f],[$c]], $res);
    }

    public function test_compile_any() {
        $var = new V\Any([new V\Classes, new V\Methods()]);
        $compiled = $var->compile();

        $f = $this->db->_file("source.php", "A\nB");
        $c = $this->db->_class("AClass", $f, 1,2);
        $m1 = $this->db->_method("a_method", $c, $f, 1, 2);
        $m2 = $this->db->_method_reference("another_method", $f, 1);
        $g = $this->db->_global("a_global", $f, 1, 2);

        $res = $this->db->query()
            ->filter($compiled)
            ->extract(function($n,&$r) {
                $r[] = $n;
            })
            ->run([]);
        $this->assertEquals([[$c],[$m1],[$m2]], $res);
    }

    public function test_compile_any_negated() {
        $var = new V\Any([new V\Classes, new V\Methods()]);
        $compiled = $var->compile(true);

        $f = $this->db->_file("source.php", "A\nB");
        $c = $this->db->_class("AClass", $f, 1,2);
        $m1 = $this->db->_method("a_method", $c, $f, 1, 2);
        $m2 = $this->db->_method_reference("another_method", $f, 1);
        $g = $this->db->_global("a_global", $f, 1, 2);

        $res = $this->db->query()
            ->filter($compiled)
            ->extract(function($n,&$r) {
                $r[] = $n;
            })
            ->run([]);
        $this->assertEquals([[$f],[$g]], $res);
    }


}
