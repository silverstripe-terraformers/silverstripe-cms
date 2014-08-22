<?php
/**
 * @package cms
 * @subpackage tests
 */
class ContentControllerTest extends FunctionalTest {
	
	protected static $fixture_file = 'ContentControllerTest.yml';
	
	protected static $use_draft_site = true;
	
	/**
	 * Test that nested pages, basic actions, and nested/non-nested URL switching works properly
	 */

	public function testNestedPages() {
		RootURLController::reset();
		Config::inst()->update('SiteTree', 'nested_urls', true);

		$this->assertEquals('Home Page', $this->get('/')->getBody());
		$this->assertEquals('Home Page', $this->get('/home/index/')->getBody());
		$this->assertEquals('Home Page', $this->get('/home/second-index/')->getBody());

		$this->assertEquals('Second Level Page', $this->get('/home/second-level/')->getBody());
		$this->assertEquals('Second Level Page', $this->get('/home/second-level/index/')->getBody());
		$this->assertEquals('Second Level Page', $this->get('/home/second-level/second-index/')->getBody());

		$this->assertEquals('Third Level Page', $this->get('/home/second-level/third-level/')->getBody());
		$this->assertEquals('Third Level Page', $this->get('/home/second-level/third-level/index/')->getBody());
		$this->assertEquals('Third Level Page', $this->get('/home/second-level/third-level/second-index/')->getBody());

		RootURLController::reset();
		SiteTree::config()->nested_urls = false;

		$this->assertEquals('Home Page', $this->get('/')->getBody());
		$this->assertEquals('Home Page', $this->get('/home/')->getBody());
		$this->assertEquals('Home Page', $this->get('/home/second-index/')->getBody());

		$this->assertEquals('Second Level Page', $this->get('/second-level/')->getBody());
		$this->assertEquals('Second Level Page', $this->get('/second-level/index/')->getBody());
		$this->assertEquals('Second Level Page', $this->get('/second-level/second-index/')->getBody());

		$this->assertEquals('Third Level Page', $this->get('/third-level/')->getBody());
		$this->assertEquals('Third Level Page', $this->get('/third-level/index/')->getBody());
		$this->assertEquals('Third Level Page', $this->get('/third-level/second-index/')->getBody());
	}

	/**
	 * Tests that a non-existant media url does not leak controller info
	 */
	public function testNonExistantMediaUrl() {
		RootURLController::reset();
		Config::inst()->update('SiteTree', 'nested_urls', true);

		$this->assertContains('Page not found',$this->get('/home/second-level/foo.css')->getBody());
	}

	/**
	 * Tests {@link ContentController::ChildrenOf()}
	 */
	public function testChildrenOf() {
		$controller = new ContentController();

		Config::inst()->update('SiteTree', 'nested_urls', true);

		$this->assertEquals(1, $controller->ChildrenOf('/')->Count());
		$this->assertEquals(1, $controller->ChildrenOf('/home/')->Count());
		$this->assertEquals(2, $controller->ChildrenOf('/home/second-level/')->Count());
		$this->assertEquals(0, $controller->ChildrenOf('/home/second-level/third-level/')->Count());

		SiteTree::config()->nested_urls = false;

		$this->assertEquals(1, $controller->ChildrenOf('/')->Count());
		$this->assertEquals(1, $controller->ChildrenOf('/home/')->Count());
		$this->assertEquals(2, $controller->ChildrenOf('/second-level/')->Count());
		$this->assertEquals(0, $controller->ChildrenOf('/third-level/')->Count());
	}

	public function testDeepNestedURLs() {
		Config::inst()->update('SiteTree', 'nested_urls', true);

		$page = new Page();
		$page->URLSegment = 'base-page';
		$page->write();

		for($i = 0; $i < 10; $i++) {
			$parentID = $page->ID;

			$page = new ContentControllerTest_Page();
			$page->ParentID = $parentID;
			$page->Title      = "Page Level $i";
			$page->URLSegment = "level-$i";
			$page->write();

			$relativeLink = Director::makeRelative($page->Link());
			$this->assertEquals($page->Title, $this->get($relativeLink)->getBody());
		}


		SiteTree::config()->nested_urls = false;
	}

	public function testViewDraft(){
		
		// test when user does not have permission, should get login form
		$this->logInWithPermission('EDITOR');
		$this->assertEquals('403', $this->get('/contact/?stage=Stage')->getstatusCode());
		
		
		// test when user does have permission, should show page title and header ok.
		$this->logInWithPermission('ADMIN');
		$this->assertEquals('200', $this->get('/contact/?stage=Stage')->getstatusCode());
		
		
	}
	
	public function testLinkShortcodes() {
		$linkedPage = new SiteTree();
		$linkedPage->URLSegment = 'linked-page';
		$linkedPage->write();
		$linkedPage->publish('Stage', 'Live');
		
		$page = new SiteTree();
		$page->URLSegment = 'linking-page';
		$page->Content = sprintf('<a href="[sitetree_link,id=%s]">Testlink</a>', $linkedPage->ID);
		$page->write();
		$page->publish('Stage', 'Live');

		$this->assertContains(
			sprintf('<a href="%s">Testlink</a>', $linkedPage->Link()),
			$this->get($page->RelativeLink())->getBody(),
			'"sitetree_link" shortcodes get parsed properly'
		);
	}

}

class ContentControllerTest_Page extends Page {  }

class ContentControllerTest_Page_Controller extends Page_Controller {

	private static $allowed_actions = array (
		'second_index'
	);

	public function index() {
		return $this->Title;
	}

	public function second_index() {
		return $this->index();
	}

}

