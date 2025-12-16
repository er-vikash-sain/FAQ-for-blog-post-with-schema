<?php
/**
 * Schema tests.
 *
 * @package FAQ_Plugin
 */

class FAQ_Plugin_Schema_Test extends WP_UnitTestCase {

	/**
	 * Ensure schema array shape is correct and strips tags/shortcodes.
	 */
	public function test_schema_builds_expected_shape() {
		$schema = FAQ_Plugin_Schema::get_instance()->build_schema_array(
			array(
				array(
					'question' => 'What is this? [shortcode]',
					'answer'   => '<strong>Great</strong> answer.',
				),
			)
		);

		$this->assertIsArray( $schema );
		$this->assertSame( 'https://schema.org', $schema['@context'] );
		$this->assertSame( 'FAQPage', $schema['@type'] );
		$this->assertArrayHasKey( 'mainEntity', $schema );
		$this->assertCount( 1, $schema['mainEntity'] );

		$entity = $schema['mainEntity'][0];
		$this->assertSame( 'Question', $entity['@type'] );
		$this->assertSame( 'What is this?', $entity['name'] );
		$this->assertSame( 'Answer', $entity['acceptedAnswer']['@type'] );
		$this->assertSame( 'Great answer.', $entity['acceptedAnswer']['text'] );
	}
}
