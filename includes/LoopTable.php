<?php
/**
 * A parser extension that adds the tag <loop_table> to mark content as table and provide a table of tables
 *
 * @ingroup Extensions
 *
 */
class LoopTable extends LoopObject{

	public static $mTag = 'loop_table';
	public static $mIcon = 'table';
	
	/**
	 * {@inheritDoc}
	 * @see LoopObject::getShowNumber()
	 */
	public function getShowNumber() {
		global $wgLoopTableNumbering;
		return $wgLoopTableNumbering;
	}
	
	/**
	 * {@inheritDoc}
	 * @see LoopObject::getDefaultRenderOption()
	 */
	public function getDefaultRenderOption() {
		global $wgLoopTableDefaultRenderOption;
		return $wgLoopTableDefaultRenderOption;
	}
		
	
	/**
	 *
	 * @param string $input        	
	 * @param array $args        	
	 * @param Parser $parser        	
	 * @param Frame $frame        	
	 * @return string
	 */
	public static function renderLoopTable($input, array $args, $parser, $frame) {
		try {
			$table = new LoopTable();
			$table->init($input, $args, $parser, $frame);
			$table->parse();
			$html = $table->render();
		} catch ( LoopException $e ) {
			$parser->addTrackingCategory( 'loop-tracking-category-loop-error' );
			$html = "$e";
		}
		return  $html ;		
	}
	
	

}

/**
 * Display list of tables for current structure
 * 
 * @author vorreitm, krohnden
 *        
 */
class SpecialLoopTables extends SpecialPage {
	
	public function __construct() {
		parent::__construct ( 'LoopTables' );
	}
	
	public function execute($sub) {
		global $wgParserConf, $wgLoopNumberingType;
		
		$config = $this->getConfig ();
		$request = $this->getRequest ();
		
		$out = $this->getOutput ();
		
		$out->setPageTitle ( $this->msg ( 'looptables-specialpage-title' ) );
		
		$out->addHtml ( '<h1>' );
		$out->addWikiMsg ( 'looptables-specialpage-title' );
		$out->addHtml ( '</h1>' );
		
		$loopStructure = new LoopStructure();
		$loopStructure->loadStructureItems();
		
		$parser = new Parser ( $wgParserConf );
		$parserOptions = ParserOptions::newFromUser ( $this->getUser () );
		$parser->Options ( $parserOptions );		
		
		$tables = array ();
		$items = $loopStructure->getStructureItems();
		$table_number = 1;
		foreach ( $items as $item ) {
			
			$article_id = $item->getArticle ();
			$title = Title::newFromID ( $article_id );
			$rev = Revision::newFromTitle ( $title );
			$content = $rev->getContent ();
			$table_tags = array ();
			
			$parser->clearState();
			$parser->setTitle ( $title );
			
			
			$table_tags = LoopIndex::getObjectsOfType ( 'loop_table' );
			
			if ( isset( $table_tags[$article_id] ) ) {
				foreach ( $table_tags[$article_id] as $table_tag ) {
				
					$table = new LoopTable();
					$table->init($table_tag["thumb"], $table_tag["args"]);
					
					$table->parse(true);
					if ( $wgLoopNumberingType == "chapter" ) {
						$table->setNumber ( $table_tag["nthoftype"] );
					} elseif ( $wgLoopNumberingType == "ongoing" ) {
						$table->setNumber ( $table_number );
						$table_number ++;
					}
					$table->setArticleId ( $article_id );
					
					$out->addHtml ( $table->renderForSpecialpage () );
				}
			}
		}
	}
	protected function getGroupName() {
		return 'loop';
	}
}

