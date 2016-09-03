<?php
class Pn_Initializer2
{
	private $pages = array('Home', 'Blog', 'Search');

	public function newBlog()
	{

		$dev_ctr_id = $this->getLastBlogCreated();

		# switch to the newly created blog
		switch_to_blog($dev_ctr_id);

		# change to our theme
		switch_theme('dwboomer');

		# make site private
		update_option('blog_public', -2, 'yes');

		$this->httpsOurSite();

		$this->setDefaultPages();

		$this->setDefaultMenu($this->pages);

		restore_current_blog();
	}

	private function getLastBlogCreated()
	{
		$blogs = wp_get_sites();
		$new_blog = array_pop($blogs);
		return $new_blog['blog_id'];
	}

	private function httpsOurSite()
	{
		if (!$this->isThisLocalhost()) {
			$url = get_option('siteurl');
			$url = explode('://', $url);
			$the_url = 'https://' . $url[1];
			$this->updateUrls($the_url);
		}
	}
		private function isThisLocalhost() {
			if ($_SERVER['HTTP_HOST'] == 'localhost') {
				return true;
			}
			return false;
		}

		private function updateUrls($the_url)
		{
			update_option('siteurl', $the_url);
		    	update_option('home', $the_url);
		}

	private function setDefaultPages()
	{
		// 1. make front page static instead of posts
		update_option('show_on_front', 'page');

		// 2. for each $pages we declared
			// a. check if they exist, return if true
			// b. otherwise insert the new page into db
				// i. create page, retrieve id
				// ii. wp_insert_post

		foreach ($this->pages as $page) {
			if ($this->doThesePagesAlreadyExist($page) === false) {
				$pageid = $this->insertPage($page);
				$this->doThingsForThesePages($page, $pageid);
			}
		}
	}


		private function doThesePagesAlreadyExist(&$name)
		{
			$wp_pages = get_pages();
			foreach ($wp_pages as $wp_page) {
				if ($wp_page->post_title == $name) {
					return $wp_page->ID;
				}
				return false;
			}
		}

		private function insertPage($page)
		{
			$new_page = $this->createPage($page);
			$pageid = wp_insert_post($new_page);
			return $pageid;
		}

		private function createPage($name)
		{
			global $user_ID; # to set post author

			# set up page array
			$page = array();
			$page['post_type'] = 'page';
			$page['post_content'] = '';
			$page['post_parent'] = 0;
			$page['post_author'] = $user_ID;
			$page['post_status'] = 'publish';

			# make sure the name is a string -- is it even necessary? who calls this outside of here?
			if (is_string($name))
				$pagename = ucwords(strtolower($name));

			$page['post_title'] = $pagename;

			return $page;
		}

		private function doThingsForThesePages($page, $pageid) {
			if ($page == 'Home') {
				update_option('page_on_front', $pageid);
			} elseif ($page == 'Blog') {
				update_option('page_for_posts', $pageid);
			} elseif ($page == 'Search') {
				$content = '<gcse:searchresults-only linkTarget="_self"></gcse:searchresults-only>';
				wp_update_post(array('ID'=>$pageid,'post_content'=>$content));
			}
		}

	private function setDefaultMenu(&$pages)
	{
		# check if menu exists
		$menu_exists = wp_get_nav_menu_object('main');

		# create it
		if (!$menu_exists) {
			$this->createMenu();
		}

		return;
	}
		private function createMenu() 
		{
			$menu_id = wp_create_nav_menu('main');

			$this->setMenuLocation($menu_id);

			$this->setMenuItems($menu_id);
		}
		private function setMenuLocation($menu_id) 
		{
			# let's set this as the Main Menu within the Theme
	    		$locations = get_theme_mod('nav_menu_locations');
	    		$locations['main'] = $menu_id;
	    		set_theme_mod( 'nav_menu_locations', $locations );
		}

		private function setMenuItems($menu_id) 
		{
			foreach ($this->pages as $page) {
				if ($page != 'Search') {
			        		wp_update_nav_menu_item($menu_id, 0, array(
						'menu-item-title' => __($page),
						'menu-item-object' => 'page',
						'menu-item-object-id' => get_page_by_path($page)->ID,
						'menu-item-type' => 'post_type',
						'menu-item-status' => 'publish',
					));
		        		}
		                }
		}
}
function goBootstrapper() {
	$bootstrapper = new Pn_Initializer2();
	$bootstrapper->newBlog();
}
add_action('wpmu_new_blog', 'goBootstrapper');