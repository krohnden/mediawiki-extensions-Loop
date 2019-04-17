<?php
class LoopIndex {

    public $id; // id of the indexed item
	public $pageId; // article id of the page the item is on
	public $refId; // reference id of the item
	public $index; // the index an item is displayed in (figure, table, etc)
	public $nthItem; // the n-th item of that index on that page
	public $itemType; // the item's type (only for loop_media: rollover, animation, etc)
	public $itemTitle; // the entered title 
	public $itemDescription; // the entered description
	public $itemThumb; // preview image (only for loop_figure)

	/**
	 * Add indexable item to the database
	 * @return bool true
	 */
	public function addToDatabase() {

        $dbw = wfGetDB( DB_MASTER );
        
        $dbw->insert(
            'loop_index',
            array(
                'li_pageid' => $this->pageId,
                'li_refid' => $this->refId,
                'li_index' => $this->index,
                'li_nthoftype' => $this->nthItem,
                'li_itemtype' => $this->itemType,
                'li_itemtitle' => $this->itemTitle,
                'li_itemdesc' => $this->itemDescription,
                'li_itemthumb' => $this->itemThumb
            ),
            __METHOD__
        );
        $this->id = $dbw->insertId();
        return true;

    }
	
	// deletes all objects of a page
    public static function removeAllPageItemsFromDb ( $article ) {

		$dbr = wfGetDB( DB_SLAVE );
		$dbr->delete(
			'loop_index',
			'li_pageid = ' . $article,
			__METHOD__
		);

        return true;
    }

	// returns ALL objects of a type in the wiki.
    public static function getObjectsOfType ( $type ) {

		$dbr = wfGetDB( DB_SLAVE );

		$res = $dbr->select(
			'loop_index',
			array(
                'li_pageid',
                'li_refid',
				'li_index',
                'li_nthoftype',
                'li_itemtype',
                'li_itemtitle',
                'li_itemdesc',
                'li_itemthumb'
			),
			array(
				'li_index = "' . $type .'"'
			),
			__METHOD__
		);
		
		$objects = array(  );
		foreach( $res as $row ) {
			$objects[$row->li_pageid][$row->li_nthoftype] = array(
				"args" => array("id" => $row->li_refid,
					"title" => $row->li_itemtitle,
					"description" => $row->li_itemdesc,
					"type" => $row->li_itemtype
			),
				"thumb" => $row->li_itemthumb
			);
		}
		return $objects;
	}

	// returns number of objects in structure before the given structureItem
    public static function getObjectNumberingsForPage ( LoopStructureItem $lsi, LoopStructure $loopStructure ) {

		$objects = array();
		foreach (LoopObject::$mObjectTypes as $objectType) {
			$objects[$objectType] = array(); 
			$return[$objectType] = 0; 
		}

		$dbr = wfGetDB( DB_SLAVE );

		$res = $dbr->select(
			'loop_index',
			array(
                'li_pageid',
                'li_refid',
                'li_index'
			),
			"*",
			__METHOD__
		);
		foreach( $res as $row ) {
			$objects[$row->li_index][$row->li_pageid][] = $row->li_refid;
		}

		$structureItems = $loopStructure->getStructureItems();
		foreach ( $structureItems as $item ) {
			$tmpId = $item->article;
			if (  $item->sequence < $lsi->sequence  ) {
				foreach( $objects as $objectType => $page ) {
					if ( isset( $page[$tmpId] ) ) {
						$return[$objectType] += sizeof($page[$tmpId]);
					}
				}
			}
			
		}

        return $return;
	}

}