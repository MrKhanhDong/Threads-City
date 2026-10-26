<?php

namespace Tests\Feature;

use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class ViewQueryIsolationTest extends TestCase
{
    #[TestWith(['auth.login'])]
    #[TestWith(['auth.register'])]
    #[TestWith(['threads.index'])]
    #[TestWith(['threads.partials.thread_card'])]
    #[TestWith(['threads.partials.thread_list'])]
    #[TestWith(['profile.show'])]
    #[TestWith(['account.edit'])]
    public function test_page_and_card_composers_do_not_query_unused_sidebar_or_blog_data(string $name): void
    {
        $resolver = Model::getConnectionResolver();
        $isolatedResolver = $this->createMock(ConnectionResolverInterface::class);
        $isolatedResolver->expects($this->never())->method('connection');
        Model::setConnectionResolver($isolatedResolver);

        try {
            $view = view($name);
            app('events')->dispatch('composing: '.$name, [$view]);
            $this->assertFalse(app()->resolved('blog.context'));
            $this->assertArrayNotHasKey('suggestedUsers', $view->getData());
        } finally {
            Model::setConnectionResolver($resolver);
        }
    }
}
