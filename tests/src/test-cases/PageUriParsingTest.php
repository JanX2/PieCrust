<?php

require_once 'unittest_setup.php';

require_once 'vfsStream/vfsStream.php';

use PieCrust\PieCrust;
use PieCrust\PieCrustDefaults;
use PieCrust\Page\Page;
use PieCrust\Util\UriParser;
use PieCrust\Util\UriBuilder;


class PageUriParsingTest extends PHPUnit_Framework_TestCase
{
    protected function makeUriInfo($uri, $path, $wasPathChecked, $type = Page::TYPE_REGULAR, $blogKey = null, $key = null, $date = null, $pageNumber = 1)
    {
        return array(
                'uri' => $uri,
                'page' => $pageNumber,
                'type' => $type,
                'blogKey' => $blogKey,
                'key' => $key,
                'date' => $date,
                'path' => $path,
                'was_path_checked' => $wasPathChecked
            );
    }
    
    public function parseUriDataProvider()
    {
        $pagesDir = vfsStream::url('root/kitchen/_content/pages/');
        $postsDir = vfsStream::url('root/kitchen/_content/posts/');
        return array(
            array(
                array(),
                '',
                $this->makeUriInfo('', $pagesDir . '_index.html', true)
            ),
            array(
                array(),
                '/',
                $this->makeUriInfo('', $pagesDir . '_index.html', true)
            ),
            array(
                array(),
                '/2',
                $this->makeUriInfo('', $pagesDir . '_index.html', true, Page::TYPE_REGULAR, null, null, null, 2)
            ),
            array(
                array(),
                '/existing-page',
                $this->makeUriInfo('existing-page', $pagesDir . 'existing-page.html', true)
            ),
            array(
                array(),
                '/existing-page/2',
                $this->makeUriInfo('existing-page', $pagesDir . 'existing-page.html', true, Page::TYPE_REGULAR, null, null, null, 2)
            ),
            array(
                array(),
                '/existing-page.ext',
                $this->makeUriInfo('existing-page.ext', $pagesDir . 'existing-page.html', true)
            ),
            array(
                array(),
                '/blah',
                $this->makeUriInfo('blah', $pagesDir . PieCrustDefaults::CATEGORY_PAGE_NAME . '.html', false, Page::TYPE_CATEGORY, 'blog', 'blah')
            ),
            array(
                array(),
                '/tag/blah',
                $this->makeUriInfo('tag/blah', $pagesDir . PieCrustDefaults::TAG_PAGE_NAME . '.html', false, Page::TYPE_TAG, 'blog', 'blah')
            ),
            array(
                array(),
                '/blah.ext',
                null
            ),
            array(
                array(),
                '2011/02/03/some-post',
                $this->makeUriInfo('2011/02/03/some-post', $postsDir . '2011-02-03_some-post.html', false, Page::TYPE_POST, 'blog', null, mktime(0, 0, 0, 2, 3, 2011))
            ),
            array(
                array(
                    'site' => array('blogs' => array('blogone', 'blogtwo'))
                ),
                '/blogone/2011/02/03/some-post',
                $this->makeUriInfo('blogone/2011/02/03/some-post', $postsDir . 'blogone/2011-02-03_some-post.html', false, Page::TYPE_POST, 'blogone', null, mktime(0, 0, 0, 2, 3, 2011))
            ),
            array(
                array(
                    'site' => array('blogs' => array('blogone', 'blogtwo'))
                ),
                '/blogtwo/2011/02/03/some-post',
                $this->makeUriInfo('blogtwo/2011/02/03/some-post', $postsDir . 'blogtwo/2011-02-03_some-post.html', false, Page::TYPE_POST, 'blogtwo', null, mktime(0, 0, 0, 2, 3, 2011))
            )
         );
    }

    /**
     * @dataProvider parseUriDataProvider
     */
    public function testParseUri($config, $uri, $expectedUriInfo)
    {
        $fs = MockFileSystem::create()
            ->withPostsDir()
            ->withPage('_index')
            ->withPage('existing-page');

        $pc = new PieCrust(array('root' => $fs->siteRootUrl(), 'debug' => true, 'cache' => false));
        $pc->getConfig()->set($config);
        $pc->getConfig()->setValue('site/root', 'http://whatever/');
        
        $uriInfo = UriParser::parseUri($pc, $uri);
        $this->assertEquals($expectedUriInfo, $uriInfo, 'The URI info was not what was expected.');
    }

    public function testParseRegularOnlyUri()
    {
        $fs = MockFileSystem::create()
            ->withPage('existing-page');

        $pc = new PieCrust(array('root' => $fs->siteRootUrl(), 'cache' => false));
        $uriInfo = UriParser::parseUri($pc, '/existing-page');
        $this->assertEquals(
            $this->makeUriInfo('existing-page', $fs->url('kitchen/_content/pages/existing-page.html'), true),
            $uriInfo
        );
    }

    public function testParseRegularOnlyUriThatDoesntExist()
    {
        $fs = MockFileSystem::create()
            ->withPage('existing-page');

        $pc = new PieCrust(array('root' => $fs->siteRootUrl(), 'cache' => false));
        $uriInfo = UriParser::parseUri($pc, '/non-existing-page', UriParser::PAGE_URI_REGULAR);
        $this->assertNull($uriInfo);
    }
}
