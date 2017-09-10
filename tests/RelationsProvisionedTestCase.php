<?php
namespace Czim\DataStore\Test;

use Czim\DataStore\Test\Helpers\Models\TestAuthor;
use Czim\DataStore\Test\Helpers\Models\TestComment;
use Czim\DataStore\Test\Helpers\Models\TestGenre;
use Czim\DataStore\Test\Helpers\Models\TestPost;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Schema;

abstract class RelationsProvisionedTestCase extends ProvisionedTestCase
{

    /**
     * Sets up the database for testing.
     */
    protected function setUpDatabase()
    {
        Schema::create('test_genres', function($table) {
            $table->increments('id');
            $table->string('name', 50);
            $table->timestamps();
        });

        Schema::create('test_authors', function($table) {
            $table->increments('id');
            $table->string('name', 255);
            $table->timestamps();
        });

        Schema::create('test_posts', function($table) {
            $table->increments('id');
            $table->integer('test_genre_id')->nullable()->unsigned();
            $table->string('title', 50);
            $table->string('unfillable', 20)->nullable();
            $table->timestamps();
        });

        Schema::create('test_comments', function($table) {
            $table->increments('id');
            $table->integer('test_post_id')->nullable()->unsigned();
            $table->integer('test_author_id')->nullable()->unsigned();
            $table->integer('test_has_one_post_id')->nullable()->unsigned();
            $table->string('title', 50);
            $table->timestamps();
        });

        Schema::create('test_author_test_post', function($table) {
            $table->increments('id');
            $table->integer('test_author_id')->unsigned();
            $table->integer('test_post_id')->unsigned();
        });

        Schema::create('test_tags', function($table) {
            $table->increments('id');
            $table->integer('taggable_id')->unsigned()->nullable();
            $table->string('taggable_type', 255)->nullable();
            $table->string('name', 50);
            $table->timestamps();
        });

        Schema::create('test_specials', function($table) {
            $table->string('special', 20)->unique();
            $table->integer('post_id')->unsigned()->nullable();
            $table->string('name', 50);
            $table->timestamps();
            $table->primary(['special']);
        });

        Schema::create('taggables', function($table) {
            $table->integer('test_tag_id')->unsigned()->nullable();
            $table->integer('taggable_id')->unsigned()->nullable();
            $table->string('taggable_type', 255)->nullable();
        });
    }

    /**
     * Seeds basic test data.
     */
    protected function seedDatabase()
    {
        $genreA = TestGenre::create(['name' => 'Test A']);
        $genreB = TestGenre::create(['name' => 'Test B']);

        /** @var TestPost $post */
        $post = new TestPost(['title' => 'Test Post']);
        $post->genre()->associate($genreA);
        $post->save();

        $commentA = new TestComment(['title' => 'Test Comment 1']);
        $commentB = new TestComment(['title' => 'Test Comment 2']);
        $commentC = new TestComment(['title' => 'Test Comment 3']);

        $post->comments()->saveMany([
            $commentA,
            $commentB,
            $commentC,
        ]);

        $authorA = new TestAuthor(['name' => 'Author A']);
        $authorB = new TestAuthor(['name' => 'Author B']);

        $post->authors()->saveMany(new Collection([ $authorA, $authorB ]));
    }

}
