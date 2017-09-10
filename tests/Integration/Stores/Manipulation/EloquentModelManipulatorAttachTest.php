<?php
namespace Czim\DataStore\Test\Integration\Stores\Manipulation;

use Czim\DataStore\Stores\Manipulation\EloquentModelManipulator;
use Czim\DataStore\Test\Helpers\Models\TestAuthor;
use Czim\DataStore\Test\Helpers\Models\TestComment;
use Czim\DataStore\Test\Helpers\Models\TestGenre;
use Czim\DataStore\Test\Helpers\Models\TestPost;
use Czim\DataStore\Test\Helpers\Models\TestTag;
use Czim\DataStore\Test\RelationsProvisionedTestCase;
use Illuminate\Support\Collection;

class EloquentModelManipulatorAttachTest extends RelationsProvisionedTestCase
{

    /**
     * {@inheritdoc}
     */
    public function setUp()
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
    function it_attaches_a_belongs_to_model()
    {
        /** @var TestPost $post */
        $post = TestPost::first();
        $post->genre()->dissociate();
        $post->save();

        /** @var TestGenre $genre */
        $genre = TestGenre::first();

        $manipulator = new EloquentModelManipulator;
        $manipulator->setModel($post);

        static::assertTrue($manipulator->attachRelatedRecords($post, 'genre', [ $genre ]));

        $post = $post->fresh();

        static::assertEquals($genre->id, $post->test_genre_id);
    }

    /**
     * @test
     */
    function it_attaches_a_belongs_to_model_that_was_previously_attached_to_another()
    {
        /** @var TestPost $post */
        $post = TestPost::first();

        /** @var TestGenre $genre */
        $genre = TestGenre::find(2);

        $manipulator = new EloquentModelManipulator;
        $manipulator->setModel($post);

        static::assertTrue($manipulator->attachRelatedRecords($post, 'genre', new Collection([ $genre ])));

        $post = $post->fresh();

        static::assertEquals($genre->id, $post->test_genre_id);
    }

    /**
     * @test
     */
    function it_detaches_a_belongs_to_model_that_was_previously_attached()
    {
        /** @var TestPost $post */
        $post = TestPost::first();

        $manipulator = new EloquentModelManipulator;
        $manipulator->setModel($post);

        static::assertTrue($manipulator->attachRelatedRecords($post, 'genre', []));

        $post = $post->fresh();

        static::assertEquals(null, $post->test_genre_id);
    }

    /**
     * @test
     */
    function it_attaches_a_belongs_to_model_deleting_the_previous()
    {
        // Set up deletion
        $config = [
            'delete-detached' => [
                'genre' => true,
            ],
        ];

        /** @var TestPost $post */
        $post = TestPost::first();

        /** @var TestGenre $genre */
        $genre = TestGenre::find(2);

        $manipulator = new EloquentModelManipulator;
        $manipulator->setModel($post);
        $manipulator->setConfig($config);

        static::assertTrue($manipulator->attachRelatedRecords($post, 'genre', [ $genre ]));

        $post = $post->fresh();

        static::assertEquals($genre->id, $post->test_genre_id);

        static::assertNull(TestGenre::find(1), "Previously related was not deleted");
    }

    // Has One

    /**
     * @test
     */
    function it_attaches_a_has_one_model()
    {
        /** @var TestPost $post */
        $post = TestPost::first();
        $post->commentHasOne()->save(TestComment::find(1));

        /** @var TestComment $comment */
        $comment = TestComment::find(2);

        $manipulator = new EloquentModelManipulator;
        $manipulator->setModel($post);

        static::assertTrue($manipulator->attachRelatedRecords($post, 'commentHasOne', [ $comment ]));

        $post = $post->fresh();

        static::assertEquals($comment->id, $post->commentHasOne->id);
        static::assertNotNull(TestComment::find(1), "Previously related was deleted");
    }

    /**
     * @test
     */
    function it_attaches_a_has_one_model_deleting_the_previous()
    {
        // Set up deletion
        $config = [
            'delete-detached' => [
                'commentHasOne' => true,
            ],
        ];

        /** @var TestPost $post */
        $post = TestPost::first();
        $post->commentHasOne()->save(TestComment::find(1));

        /** @var TestComment $comment */
        $comment = TestComment::find(2);

        $manipulator = new EloquentModelManipulator;
        $manipulator->setModel($post);
        $manipulator->setConfig($config);

        static::assertTrue($manipulator->attachRelatedRecords($post, 'commentHasOne', [ $comment ]));

        $post = $post->fresh();

        static::assertEquals($comment->id, $post->commentHasOne->id);
        static::assertNull(TestComment::find(1), "Previously related was not deleted");
    }

    // Morph To

    /**
     * @test
     */
    function it_attaches_a_morph_to_model()
    {
        /** @var TestPost $post */
        $post = TestPost::find(1);

        $tag = new TestTag(['name' => 'Test']);
        $tag->taggable()->associate($post);
        $tag->save();

        /** @var TestPost $newPost */
        $newPost = new TestPost(['title' => 'Testing new']);

        $manipulator = new EloquentModelManipulator;
        $manipulator->setModel($tag);

        static::assertTrue($manipulator->attachRelatedRecords($tag, 'taggable', [ $newPost ]));

        $tag = $tag->fresh();

        static::assertEquals($newPost->id, $tag->taggable_id);
        static::assertEquals(get_class($newPost), $tag->taggable_type);
    }

    /**
     * @test
     */
    function it_attaches_a_morph_to_model_deleting_the_previous()
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

        /** @var TestPost $newPost */
        $newPost = new TestPost(['title' => 'Testing new']);

        $manipulator = new EloquentModelManipulator;
        $manipulator->setModel($tag);
        $manipulator->setConfig($config);

        static::assertTrue($manipulator->attachRelatedRecords($tag, 'taggable', [ $newPost ]));

        $tag = $tag->fresh();

        static::assertEquals($newPost->id, $tag->taggable_id);
        static::assertEquals(get_class($newPost), $tag->taggable_type);

        static::assertNull(TestPost::find(1), "Previously related was not deleted");
    }

    // Morph One

    /**
     * @test
     */
    function it_attaches_a_morph_one_model()
    {
        $tag = new TestTag(['name' => 'Test']);
        $tag->save();

        /** @var TestPost $post */
        $post = TestPost::first();
        $post->tagMorphOne()->save($tag);

        $newTag = new TestTag(['name' => 'Test']);

        $manipulator = new EloquentModelManipulator;
        $manipulator->setModel($post);

        static::assertTrue($manipulator->attachRelatedRecords($post, 'tagMorphOne', [ $newTag ]));

        $post = $post->fresh();

        static::assertEquals($newTag->id, $post->tagMorphOne->id);
        static::assertEquals(get_class($newTag), get_class($post->tagMorphOne));

        static::assertNotNull(TestTag::find($tag->id), "Previously related was deleted");
    }


    // ------------------------------------------------------------------------------
    //      Plural Relationships
    // ------------------------------------------------------------------------------

    // Has Many

    /**
     * @test
     */
    function it_attaches_has_many_models_in_addition_to_existing_related()
    {
        /** @var TestPost $post */
        $post = TestPost::first();

        $commentPersisted = TestComment::create([
            'title' => 'New Title Persisted',
        ]);

        $related = [
            new TestComment([
                'title' => 'New Title',
            ]),
            $commentPersisted,
        ];

        $manipulator = new EloquentModelManipulator;
        $manipulator->setModel($post);

        static::assertTrue($manipulator->attachRelatedRecords($post, 'comments', $related));

        $post = $post->fresh();

        static::assertCount(5, $post->comments);
    }

    /**
     * @test
     */
    function it_attaches_has_many_models_detaching_current()
    {
        $config = [
            'allow-relationship-replace' => [
                'comments' => true,
            ],
        ];

        /** @var TestPost $post */
        $post = TestPost::first();

        $commentPersisted = TestComment::create([
            'title' => 'New Title Persisted',
        ]);

        $related = [
            new TestComment([
                'title' => 'New Title',
            ]),
            $commentPersisted,
        ];

        $manipulator = new EloquentModelManipulator;
        $manipulator->setModel($post);
        $manipulator->setConfig($config);

        static::assertTrue($manipulator->attachRelatedRecords($post, 'comments', $related, true));

        $post = $post->fresh();

        static::assertCount(2, $post->comments);

        static::assertEquals(3, TestComment::query()->whereIn('id', [1, 2, 3])->count(), "Previously related were deleted");
    }

    /**
     * @test
     */
    function it_attaches_has_many_models_detaching_current_deleting_previous()
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

        $commentPersisted = TestComment::create([
            'title' => 'New Title Persisted',
        ]);

        $related = [
            new TestComment([
                'title' => 'New Title',
            ]),
            $commentPersisted,
        ];

        $manipulator = new EloquentModelManipulator;
        $manipulator->setModel($post);
        $manipulator->setConfig($config);

        static::assertTrue($manipulator->attachRelatedRecords($post, 'comments', $related, true));

        $post = $post->fresh();

        static::assertCount(2, $post->comments);

        static::assertEquals(0, TestComment::query()->whereIn('id', [1, 2, 3])->count(), "Previously related were not deleted");
    }

    // Belongs to Many

    /**
     * @test
     */
    function it_attaches_belongs_to_many_models()
    {
        /** @var TestPost $post */
        $post = TestPost::first();

        $authorPersisted = TestAuthor::create([
            'name' => 'Persisted',
        ]);

        $related = [
            new TestAuthor([
                'name' => 'Unpersisted',
            ]),
            $authorPersisted,
        ];

        $manipulator = new EloquentModelManipulator;
        $manipulator->setModel($post);

        static::assertTrue($manipulator->attachRelatedRecords($post, 'authors', $related));

        $post = $post->fresh();

        static::assertCount(4, $post->authors);
    }

    /**
     * @test
     */
    function it_attaches_belongs_to_many_models_detaching_current()
    {
        $config = [
            'allow-relationship-replace' => [
                'authors' => true,
            ],
        ];

        /** @var TestPost $post */
        $post = TestPost::first();

        $authorPersisted = TestAuthor::create([
            'name' => 'Persisted',
        ]);

        $related = [
            new TestAuthor([
                'name' => 'Unpersisted',
            ]),
            $authorPersisted,
        ];

        $manipulator = new EloquentModelManipulator;
        $manipulator->setModel($post);
        $manipulator->setConfig($config);

        static::assertTrue($manipulator->attachRelatedRecords($post, 'authors', $related, true));

        $post = $post->fresh();

        static::assertCount(2, $post->authors);
        static::assertNotNull(TestAuthor::find(1), "Previously related was deleted");
        static::assertNotNull(TestAuthor::find(2), "Previously related was deleted");
    }

    /**
     * @test
     */
    function it_attaches_belongs_to_many_models_detaching_and_deleting_current()
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

        $authorPersisted = TestAuthor::create([
            'name' => 'Persisted',
        ]);

        $related = [
            new TestAuthor([
                'name' => 'Unpersisted',
            ]),
            $authorPersisted,
        ];

        $manipulator = new EloquentModelManipulator;
        $manipulator->setModel($post);
        $manipulator->setConfig($config);

        static::assertTrue($manipulator->attachRelatedRecords($post, 'authors', $related, true));

        $post = $post->fresh();

        static::assertCount(2, $post->authors);
        static::assertNull(TestAuthor::find(1), "Previously related was not deleted");
        static::assertNull(TestAuthor::find(2), "Previously related was not deleted");
    }

    /**
     * @test
     */
    function it_attaches_belongs_to_many_models_with_pivot_data()
    {

    }

    // Morph To Many

    /**
     * @test
     */
    function it_attaches_morph_to_many_models()
    {
        /** @var TestPost $post */
        $post = TestPost::first();

        $tagPersisted = TestTag::create([
            'name' => 'Persisted',
        ]);

        $related = [
            new TestTag([
                'name' => 'Unpersisted',
            ]),
            $tagPersisted,
        ];

        $manipulator = new EloquentModelManipulator;
        $manipulator->setModel($post);

        static::assertTrue($manipulator->attachRelatedRecords($post, 'morphTags', $related));

        $post = $post->fresh();

        static::assertCount(2, $post->morphTags);
    }

    // Morph To Many

    /**
     * @test
     */
    function it_attaches_morphed_by_many_models()
    {
        /** @var TestTag $tag */
        $tag = TestTag::create([
            'name' => 'Test Tag',
        ]);

        $postPersisted = TestPost::create([
            'title' => 'Persisted',
        ]);

        $related = [
            new TestPost([
                'title' => 'Unpersisted',
            ]),
            $postPersisted,
        ];

        $manipulator = new EloquentModelManipulator;
        $manipulator->setModel($tag);

        static::assertTrue($manipulator->attachRelatedRecords($tag, 'posts', $related));

        $tag = $tag->fresh();

        static::assertCount(2, $tag->posts);
    }


    // ------------------------------------------------------------------------------
    //      Relationship Misc.
    // ------------------------------------------------------------------------------

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    function it_throws_an_exception_if_singular_update_records_value_is_incorrect()
    {
        /** @var TestPost $post */
        $post = TestPost::first();

        $manipulator = new EloquentModelManipulator;
        $manipulator->setModel($post);

        $manipulator->attachRelatedRecords($post, 'genre', [ $this ]);
    }

    /**
     * @test
     */
    function it_does_not_change_anything_reattaching_the_same_singular_model()
    {
        /** @var TestGenre $genre */
        $genre = TestGenre::find(1);

        /** @var TestPost $post */
        $post = TestPost::first();

        $manipulator = new EloquentModelManipulator;
        $manipulator->setModel($post);

        static::assertTrue($manipulator->attachRelatedRecords($post, 'genre', [ $genre ]));

        $post = $post->fresh();

        static::assertEquals($genre->id, $post->test_genre_id);
    }

    /**
     * @test
     */
    function it_does_not_change_anything_reattaching_the_same_plural_models()
    {

    }

    /**
     * @test
     * @expectedException \Czim\DataStore\Exceptions\RelationReplaceDisallowedException
     */
    function it_throws_an_exception_if_not_allowed_to_replace_existing_plural_data()
    {
        /** @var TestPost $post */
        $post = TestPost::first();

        $manipulator = new EloquentModelManipulator;
        $manipulator->setModel($post);

        $manipulator->attachRelatedRecords($post, 'comments', [], true);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    function it_throws_an_exception_when_a_parent_argument_is_invalid()
    {
        /** @var TestPost $post */
        $post = TestPost::first();

        $manipulator = new EloquentModelManipulator;
        $manipulator->setModel($post);

        $manipulator->attachRelatedRecords($this, 'genre', []);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    function it_throws_an_exception_when_a_parent_argument_is_checked_without_a_reference_model_set()
    {
        /** @var TestPost $post */
        $post = TestPost::first();

        $manipulator = new EloquentModelManipulator;

        $manipulator->attachRelatedRecords($post, 'genre', []);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    function it_throws_an_exception_when_a_records_argument_is_not_a_collection_or_array()
    {
        /** @var TestPost $post */
        $post = TestPost::first();

        $manipulator = new EloquentModelManipulator;
        $manipulator->setModel($post);

        $manipulator->attachRelatedRecords($post, 'genre', 'not an array');
    }
    
    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    function it_throws_an_exception_if_a_relation_method_name_is_not_for_an_eloquent_relation()
    {
        /** @var TestPost $post */
        $post = TestPost::first();

        $manipulator = new EloquentModelManipulator;
        $manipulator->setModel($post);

        $manipulator->attachRelatedRecords($post, 'notARelation', []);
    }

    /**
     * @test
     * @expectedException \RuntimeException
     */
    function it_throws_an_exception_when_an_unsupported_relationship_type_is_attached()
    {
        /** @var TestPost $post */
        $post = TestPost::first();

        $manipulator = new EloquentModelManipulator;
        $manipulator->setModel($post);

        $manipulator->attachRelatedRecords($post, 'unsupported', []);
    }

}
