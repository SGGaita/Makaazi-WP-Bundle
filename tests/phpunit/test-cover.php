<?php
/**
 * Featured image and gallery cover stay in step.
 *
 * @package Makaazi
 */

require_once MAKAAZI_TESTS_ROOT . '/plugins/makaazi-core/admin/class-meta-box.php';

/**
 * @group admin
 */
class Test_Makaazi_Cover extends Makaazi_TestCase {

	/**
	 * Three attachments.
	 */
	private function images( $n = 3 ) {
		$file = get_temp_dir() . 'makaazi-cover.jpg';
		imagejpeg( imagecreatetruecolor( 40, 30 ), $file );
		$ids = array();
		for ( $i = 0; $i < $n; $i++ ) {
			$ids[] = self::factory()->attachment->create_upload_object( $file );
		}
		return $ids;
	}

	public function test_new_featured_image_becomes_the_cover() {
		list( $a, $b, $c ) = $this->images();
		$id = $this->property();
		update_post_meta( $id, '_mk_gallery', array( $a, $b ) );
		set_post_thumbnail( $id, $c ); // Chosen in the Featured image box.
		$g = Makaazi_Meta_Box::sync_cover( $id, array( $a, $b ) );
		$this->assertSame( array( $c, $a, $b ), $g );
		$this->assertSame( $c, (int) get_post_thumbnail_id( $id ) );
	}

	public function test_featured_image_already_in_gallery_moves_to_front() {
		list( $a, $b ) = $this->images( 2 );
		$id = $this->property();
		update_post_meta( $id, '_mk_gallery', array( $a, $b ) );
		set_post_thumbnail( $id, $b );
		$this->assertSame( array( $b, $a ), Makaazi_Meta_Box::sync_cover( $id, array( $a, $b ) ) );
	}

	public function test_reordering_the_gallery_changes_the_featured_image() {
		list( $a, $b ) = $this->images( 2 );
		$id = $this->property();
		update_post_meta( $id, '_mk_gallery', array( $a, $b ) );
		set_post_thumbnail( $id, $a );
		$g = Makaazi_Meta_Box::sync_cover( $id, array( $b, $a ) ); // Dragged b to the front.
		$this->assertSame( array( $b, $a ), $g );
		$this->assertSame( $b, (int) get_post_thumbnail_id( $id ) );
	}

	public function test_first_gallery_sets_featured_image() {
		list( $a, $b ) = $this->images( 2 );
		$id = $this->property();
		$this->assertSame( array( $a, $b ), Makaazi_Meta_Box::sync_cover( $id, array( $a, $b ) ) );
		$this->assertSame( $a, (int) get_post_thumbnail_id( $id ) );
	}
}
