<?php

class GitHub_Wiki_To_Readme {
	
	public $sections = array(
		'Header',
		'Description',
		'Installation',
		'Frequently Asked Questions',
		'Screenshots',
		'Changelog',
		'Upgrade Notice',
	); 
	
	public $username = 'benbalter';
	public $base = 'https://github.com/';
	public $slug = '';
	public $readme = '';
	public $faq = 'Please see (and feel free to contribute to) the [Frequently Asked Questions Wiki](%s).';
	
	function __construct( $slug = null) {
		
		if ( $slug != null )
			$this->slug = $slug;
		
	}
	
	function git_clone() {
		
		$dir = dirname( __FILE__ ) . '/';		
		exec( "rm -Rf {$dir}/wiki/" );
		exec( "/usr/local/bin/git clone --quiet {$this->base}{$this->username}/{$this->slug}.wiki.git {$dir}wiki" );
	}
	
	function slugify( $section ) {
		
		$section = preg_replace( '([^A-Za-z0-9])', '-', $section );
		$section = str_replace( '--', '-', $section );
		return $section;
		
	}
	
	function unslugify( $slug ) {
		
		$slug = str_replace( '-', ' ', $slug );
		return ucwords( $slug );
	}
	
	function make_header( $section ) {
		
		if ( $section == 'Header' )
			return '# ' . $this->unslugify( $this->slug ) . " #\n\n";
		
		return "\n\n## " . $this->unslugify( $section ) . " ##\n\n";
		
	}
	
	function merge() {
		
		//standard sections
		foreach ( $this->sections as $section ) 
			$this->merge_section( $section );
	
		//non-standard sections
		foreach ( glob( dirname( __FILE__ ) . '/wiki/*.md' ) as $file ) {
			$section = str_ireplace( dirname( __FILE__ ) . '/wiki/', '', $file );
			$section = str_ireplace( '.md', '', $section );

			if ( in_array( $section, $this->sections ) )
				continue;
			
			if ( $section == 'Home' )
				continue;

			$this->merge_section( $section );
			
		}	
						
	}
	
	function merge_section( $section ) {
		
		$file = dirname( __FILE__ ) . '/wiki/' . $this->slugify( $section ) . '.md';
		
		if ( !file_exists( $file ) )
			return;
		
		$this->readme .= $this->make_header( $section );	
		
		if ( $this->unslugify( $section ) == 'Frequently Asked Questions' ) {
		
			$this->readme .= sprintf( $this->faq, "{$this->base}{$this->username}/{$this->slug}/wiki/Frequently-Asked-Questions" );	
			return;
		}
		
		$this->readme .= file_get_contents( $file );

	}
	
	function gh_to_wp( $readme ) {
		
		$readme = $this->readme;
		$readme = preg_replace( "|^###([^#]+)###*?\s*?\n|im",  '=$1='."\n", $readme );
		$readme = preg_replace( "|^##([^#]+)##*?\s*?\n|im",  '==$1=='."\n", $readme );
		$readme = preg_replace( "|^#([^#]+)#*?\s*?\n|im", '===$1==='."\n", $readme );
		return $readme;
		
	}
	
	function output() {
		
		file_put_contents( 'readme.md', $this->readme );
		file_put_contents( 'readme.txt', $this->gh_to_wp( $this->readme ) );
		
	}
	
	function generate( $slug = null ) {
		
		if ( $slug != null)
			$this->slug = $slug;
			
		if ( $this->slug == null )
			die( 'No Plugin Slug Specified' );
			
		$this->git_clone(); 
		$this->merge();
		$this->output();
		
	}
	
}

$gh = new GitHub_Wiki_To_Readme();
$gh->generate( 'WP-Resume' );