<?php
namespace Czim\DataStore\Test\Integration\Stores\Manipulation;

use Czim\DataStore\Stores\Manipulation\EloquentModelManipulator;
use Czim\DataStore\Test\Helpers\Models\TestAuthor;
use Czim\DataStore\Test\Helpers\Models\TestComment;
use Czim\DataStore\Test\Helpers\Models\TestGenre;
use Czim\DataStore\Test\Helpers\Models\TestPost;
use Czim\DataStore\Test\Helpers\Models\TestTag;
use Czim\DataStore\Test\RelationsProvisionedTestCase;

class EloquentModelManipulatorDetachTest extends RelationsProvisionedTestCase
{

    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->seedDatabase();
    }


    // ------------------------------------------------------------------------------
    //      Singular Relationships
    // ------------------------------------------------------------------------------

    // Belongs To

    /**
     * @test
     */
    function it_detaches_a_belongs_to_model()
    {
        /** @var TestPost $post */
        $post = TestPost::first();

        $genre = $post->genre;

        $manipulator = new EloquentModelManipulator;
        $manipulator->setModel($post);

        static::assertTrue($manipulator->detachRelatedRecords($post, 'genre', [ $genre ]));

        $post = $post->fresh();

        static::assertNull($post->test_genre_id);
        static::assertNotNull(TestGenre::find(1), "Previously related was deleted");
    }

    /**
     * @test
     */
    function it_detaches_a_belongs_to_model_deleting_the_previous()
    {
        // Set up deletion
        $config = [
            'delete-detached' => [
                'genre' => true,
            ],
        ];

        /** @var TestPost $post */
        $post = TestPost::first();

        $genre = $post->genre;

        $manipulator = new EloquentModelManipulator;
        $manipulator->setModel($post);
        $manipulator->setConfig($config);

        static::assertTrue($manipulator->detachRelatedRecords($post, 'genre', [ $genre ]));

        $post = $post->fresh();

        static::assertNull($post->test_genre_id);

        static::assertNull(TestGenre::find(1), "Previously related was not deleted");
    }

    // Has One

    /**
     * @test
     */
    function it_detaches_a_has_one_model()
    {
        $comment = TestComment::find(1);

        /** @var TestPost $post */
        $post = TestPost::first();
        $post->commentHasOne()->save($comment);

        $manipulator = new EloquentModelManipulator;
        $manipulator->setModel($post);

        static::assertTrue($manipulator->detachRelatedRecords($post, 'commentHasOne', [ $comment ]));

        $post = $post->fresh();


        static::assertNull($post->commentHasOne);
        static::assertNotNull(TestComment::find(1), "Previously related was deleted");
    }

    /**
     * @test
     */
    function it_detaches_a_has_one_model_deleting_the_previous()
    {
        // Set up deletion
        $config = [
            'delete-detached' => [
                'commentHasOne' => true,
            ],
        ];

        $comment = TestComment::find(1);

        /** @var TestPost $post */
        $post = TestPost::first();
        $post->commentHasOne()->save($comment);

        $manipulator = new EloquentModelManipulator;
        $manipulator->setModel($post);
        $manipulator->setConfig($config);

        static::assertTrue($manipulator->detachRelatedRecords($post, 'commentHasOne', [ $comment ]));

        $post = $post->fresh();

        static::assertNull($post->commentHasOne);
        static::assertNull(TestComment::find(1), "Previously related was not deleted");
    }

    // Morph To

    /**
     * @test
     */
    function it_detaches_a_morph_to_model()
    {
        /** @var TestPost $post */
        $post = TestPost::find(1);

        $tag = new TestTag(['name' => 'Test']);
        $tag->taggable()->associate($post);
        $tag->save();

        $manipulator = new EloquentModelManipulator;
        $manipulator->setModel($tag);

        static::assertTrue($manipulator->detachRelatedRecords($tag, 'taggable', [ $post ]));

        $tag = $tag->fresh();

        static::assertNull($tag->taggable_id);
        static::assertNull($tag->taggable_type);
    }

    /**
     * @test
     */
    function it_detaches_a_morph_to_model_deleting_the_previous()
    {
        // Set up deletion
        $config = [
            'delete-detached' => [
                'taggable' => true,
            ],
        ];

        /** @var TestPost $post */
        $post = TestPost::find(1);

        $tag = new TestTag(['name' => 'Test']);
        $tag->taggable()->associate($post);
        $tag->save();

        $manipulator = new EloquentModelManipulator;
        $manipulator->setModel($tag);
        $manipulator->setConfig($config);

        static::assertTrue($manipulator->detachRelatedRecords($tag, 'taggable', [ $post ]));

        $tag = $tag->fresh();

        static::assertNull($tag->taggable_id);
        static::assertNull($tag->taggable_type);

        static::assertNull(TestPost::find(1), "Previously related was not deleted");
    }

    /**
     * @test
     */
    function it_detaches_a_morph_one_model()
    {
        $tag = new TestTag(['name' => 'Test']);
        $tag->save();

        /** @var TestPost $post */
        $post = TestPost::first();
        $post->tagMorphOne()->save($tag);

        $manipulator = new EloquentModelManipulator;
        $manipulator->setModel($post);

        static::assertTrue($manipulator->detachRelatedRecords($post, 'tagMorphOne', [ $tag ]));

        $post = $post->fresh();

        static::assertNull($post->tagMorphOne);

        static::assertNotNull(TestTag::find($tag->id), "Previously related was deleted");
    }


    // ------------------------------------------------------------------------------
    //      Plural Relationships
    // ------------------------------------------------------------------------------

    // Has Many

    /**
     * @test
     */
    function it_detaches_has_many_models()
    {
        /** @var TestPost $post */
        $post = TestPost::first();

        $related = [
            TestComment::find(1),
            TestComment::find(3),
        ];

        $manipulator = new EloquentModelManipulator;
        $manipulator->setModel($post);

        static::assertTrue($manipulator->detachRelatedRecords($post, 'comments', $related));

        $post = $post->fresh();

        static::assertCount(1, $post->comments);
        static::assertEquals(2, $post->comments->first()->id);

        static::assertEquals(2, TestComment::query()->whereIn('id', [1, 3])->count(), "Previously related were deleted");
    }

    /**
     * @test
     */
    function it_detaches_has_many_models_deleting_previous()
    {
        $config = [
            'allow-relationship-replace' => [
                'comments' => true,
            ],
            'delete-detached' => [
                'comments' => true,
            ],
        ];

        /** @var TestPost $post */
        $post = TestPost::first();

        $related = [
            TestComment::find(1),
            TestComment::find(3),
        ];

        $manipulator = new EloquentModelManipulator;
        $manipulator->setModel($post);
        $manipulator->setConfig($config);

        static::assertTrue($manipulator->detachRelatedRecords($post, 'comments', $related));

        $post = $post->fresh();

        static::assertCount(1, $post->comments);
        static::assertEquals(2, $post->comments->first()->id);

        static::assertEquals(0, TestComment::query()->whereIn('id', [1, 3])->count(), "Previously related were not deleted");
    }

    // Belongs to Many

    /**
     * @test
     */
    function it_detaches_belongs_to_many_models()
    {
        /** @var TestPost $post */
        $post = TestPost::first();

        $related = [
            TestAuthor::find(1),
        ];

        $manipulator = new EloquentModelManipulator;
        $manipulator->setModel($post);

        static::assertTrue($manipulator->detachRelatedRecords($post, 'authors', $related));

        $post = $post->fresh();

        static::assertCount(1, $post->authors);
        static::assertEquals(2, $post->authors->first()->id);

        static::assertNotNull(TestAuthor::find(1), 'Previously related were deleted');
    }

    /**
     * @test
     */
    function it_detaches_belongs_to_many_models_deleting_current()
    {
        $config = [
            'allow-relationship-replace' => [
                'authors' => true,
            ],
            'delete-detached' => [
                'authors' => true,
            ],
        ];

        /** @var TestPost $post */
        $post = TestPost::first();

        $related = [
            TestAuthor::find(1),
        ];

        $manipulator = new EloquentModelManipulator;
        $manipulator->setModel($post);
        $manipulator->setConfig($config);

        static::assertTrue($manipulator->detachRelatedRecords($post, 'authors', $related));

        $post = $post->fresh();

        static::assertCount(1, $post->authors);
        static::assertEquals(2, $post->authors->first()->id);

        static::assertNull(TestAuthor::find(1), "Previously related was not deleted");
    }

    // Morph To Many

    /**
     * @test
     */
    function it_detaches_morph_to_many_models()
    {
        /** @var TestPost $post */
        $post = TestPost::first();

        $detachTag = TestTag::create([
            'name' => 'Detach',
        ]);
        $keepTag = TestTag::create([
            'name' => 'Detach',
        ]);

        $post->morphTags()->saveMany([
            $detachTag,
            $keepTag,
        ]);

        $manipulator = new EloquentModelManipulator;
        $manipulator->setModel($post);

        static::assertTrue($manipulator->detachRelatedRecords($post, 'morphTags', [ $detachTag ]));

        $post = $post->fresh();

        static::assertCount(1, $post->morphTags);
        static::assertEquals($keepTag->id, $post->morphTags->first()->id);

        static::assertNotNull(TestTag::find($detachTag->id), 'Previous related was deleted');
    }

    // Morph To Many

    /**
     * @test
     */
    function it_detaches_morphed_by_many_models()
    {
        /** @var TestTag $tag */
        $tag = TestTag::create([
            'name' => 'Test Tag',
        ]);

        $detachPost = TestPost::create([
            'title' => 'Detach',
        ]);
        $keepPost = TestPost::create([
            'title' => 'Keep',
        ]);

        $tag->posts()->saveMany([
            $detachPost,
            $keepPost,
        ]);

        $manipulator = new EloquentModelManipulator;
        $manipulator->setModel($tag);

        static::assertTrue($manipulator->detachRelatedRecords($tag, 'posts', [ $detachPost ]));

        $tag = $tag->fresh();

        static::assertCount(1, $tag->posts);
        static::assertEquals($keepPost->id, $tag->posts->first()->id);

        static::assertNotNull(TestPost::find($detachPost->id), 'Previous related was deleted');
    }


    // ------------------------------------------------------------------------------
    //      Misc
    // ------------------------------------------------------------------------------

    /**
     * @test
     */
    function it_returns_false_when_detaching_null_for_singular_relation()
    {
        /** @var TestPost $post */
        $post = TestPost::first();

        $manipulator = new EloquentModelManipulator;
        $manipulator->setModel($post);

        static::assertFalse($manipulator->detachRelatedRecords($post, 'genre', [ null ]));

        $post = $post->fresh();

        static::assertNotNull($post->test_genre_id);
    }

    /**
     * @test
     */
    function it_does_not_do_anything_when_detaching_empty_set_of_records()
    {
        /** @var TestPost $post */
        $post = TestPost::first();

        $manipulator = new EloquentModelManipulator;
        $manipulator->setModel($post);

        static::assertTrue($manipulator->detachRelatedRecords($post, 'authors', []));
        static::assertTrue($manipulator->detachRelatedRecords($post, 'comments', []));
    }

    /**
     * @test
     */
    function it_ignores_records_marked_for_detaching_that_are_not_currently_attached()
    {
        $config = [
            'delete-detached' => [
                'comments' => true,
            ],
        ];

        /** @var TestPost $post */
        $post = TestPost::first();

        $comment = TestComment::create([
            'title' => 'New',
        ]);

        $related = [
            TestComment::find(1),
            $comment,
        ];

        $manipulator = new EloquentModelManipulator;
        $manipulator->setModel($post);
        $manipulator->setConfig($config);

        static::assertTrue($manipulator->detachRelatedRecords($post, 'comments', $related));

        $post = $post->fresh();

        static::assertCount(2, $post->comments);
        static::assertEquals([2, 3], $post->comments->pluck('id')->toArray());

        static::assertNotNull(TestComment::find($comment->getKey()), 'Unrelated record was still deleted');
        static::assertNull(TestComment::find(1), "Previously related was not deleted");
    }

    /**
     * @test
     */
    function it_throws_an_exception_when_an_unsupported_relationship_type_is_detached()
    {
        $this->expectException(\RuntimeException::class);

        /** @var TestPost $post */
        $post = TestPost::first();

        $manipulator = new EloquentModelManipulator;
        $manipulator->setModel($post);

        $manipulator->detachRelatedRecords($post, 'unsupported', []);
    }

}
