<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\Schema\Tests\Relation;

use Cycle\ORM\Schema;
use Cycle\Schema\Compiler;
use Cycle\Schema\Generator\GenerateRelations;
use Cycle\Schema\Generator\RenderRelations;
use Cycle\Schema\Generator\RenderTable;
use Cycle\Schema\Registry;
use Cycle\Schema\Relation\HasOne;
use Cycle\Schema\Tests\BaseTest;
use Cycle\Schema\Tests\Fixtures\Plain;
use Cycle\Schema\Tests\Fixtures\User;

abstract class HasOneRelationTest extends BaseTest
{
    public function testGenerate()
    {
        $e = Plain::define();
        $u = User::define();

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'plain');
        $r->register($u)->linkTable($u, 'default', 'user');

        (new GenerateRelations(['hasOne' => new HasOne()]))->run($r);

        $this->assertInstanceOf(HasOne::class, $r->getRelation($u, 'plain'));
    }

    public function testPackSchema()
    {
        $c = new Compiler();

        $e = Plain::define();
        $u = User::define();

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'plain');
        $r->register($u)->linkTable($u, 'default', 'user');

        (new GenerateRelations(['hasOne' => new HasOne()]))->run($r);
        $schema = $c->compile($r);

        $this->assertArrayHasKey('user', $schema);
        $this->assertArrayHasKey('plain', $schema['user'][Schema::RELATIONS]);

        $this->assertArrayHasKey('plain', $schema);
        $this->assertArrayHasKey('user_id', $schema['plain'][Schema::COLUMNS]);
    }


    public function testCustomKey()
    {
        $c = new Compiler();

        $e = Plain::define();
        $u = User::define();

        $u->getRelations()->get('plain')->getOptions()->set('outerKey', 'parent_id');

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'plain');
        $r->register($u)->linkTable($u, 'default', 'user');

        (new GenerateRelations(['hasOne' => new HasOne()]))->run($r);
        $schema = $c->compile($r);

        $this->assertArrayHasKey('user', $schema);
        $this->assertArrayHasKey('plain', $schema['user'][Schema::RELATIONS]);

        $this->assertArrayHasKey('plain', $schema);
        $this->assertArrayHasKey('parent_id', $schema['plain'][Schema::COLUMNS]);
    }

    public function testRenderTable()
    {
        $t = new RenderTable();
        $l = new RenderRelations();

        $e = Plain::define();
        $u = User::define();

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'plain');
        $r->register($u)->linkTable($u, 'default', 'user');

        $r->iterate(new GenerateRelations(['hasOne' => new HasOne()]));
        $r->iterate($t);
        $r->iterate($l);

        // RENDER!
        $t->getReflector()->run();

        $table = $this->getDriver()->getSchema('plain');
        $this->assertTrue($table->exists());
        $this->assertTrue($table->hasForeignKey('user_id'));
    }

    public function testRenderTableRedefined()
    {
        $t = new RenderTable();
        $l = new RenderRelations();

        $e = Plain::define();
        $u = User::define();

        $u->getRelations()->get('plain')->getOptions()->set('outerKey', 'parent_id');
        $u->getRelations()->get('plain')->getOptions()->set('fkCreate', false);

        $r = new Registry($this->dbal);
        $r->register($e)->linkTable($e, 'default', 'plain');
        $r->register($u)->linkTable($u, 'default', 'user');

        $r->iterate(new GenerateRelations(['hasOne' => new HasOne()]));
        $r->iterate($t);
        $r->iterate($l);

        // RENDER!
        $t->getReflector()->run();

        $table = $this->getDriver()->getSchema('plain');
        $this->assertTrue($table->exists());
        $this->assertTrue($table->hasColumn('parent_id'));
        $this->assertFalse($table->hasForeignKey('parent_id'));
    }
}