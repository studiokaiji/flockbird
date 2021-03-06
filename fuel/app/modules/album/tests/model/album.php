<?php
namespace Album;

/**
 * Model_Album class tests
 *
 * @group Modules
 * @group Model
 */
class Test_Model_Album extends \TestCase
{
	private static $album;
	private static $album_count = 0;
	private static $timeline_count = 0;
	private static $timeline_cache_count = 0;
	private static $is_check_timeline_view_cache;

	public static function setUpBeforeClass()
	{
		self::$is_check_timeline_view_cache = (is_enabled('timeline') && \Config::get('timeline.articles.cache.is_use'));
	}

	protected function setUp()
	{
		$this->set_count();
	}

	/**
	* @dataProvider update_provider
	*/
	public function test_update($self_member_id, $values, $album_image_public_flag_expected, $timeline_public_flag_expected)
	{
		$album = $this->get_album();
		$before = array(
			'name' => $album->name,
			'body' => $album->body,
			'public_flag' => $album->public_flag,
		);

		// timeline view cache 作成
		if (self::$is_check_timeline_view_cache)
		{
			$timeline_view_cache_before = \Timeline\Site_Util::make_view_cache4foreign_table_and_foreign_id('album', $album->id, \Config::get('timeline.types.album'));
		}

		// album update
		list($album, $moved_files, $is_changed) = Model_Album::save_with_relations($values, $self_member_id, $album);
		self::$album = $album;

		// 返り値の確認
		$this->assertNotEmpty($album);
		// 件数(増えていないことを確認)
		$this->assertEquals(self::$album_count, \Util_Orm::get_count_all('\Album\Model_Album'));

		// album_image の値を変更した場合の public_flag の確認
		$album_images = Model_AlbumImage::get4album_id($album->id);
		$album_image = array_shift($album_images);
		if (!empty($values['is_update_children_public_flag']))
		{
			$this->assertEquals($album_image_public_flag_expected, $album_image->public_flag);
		}

		// timeline 関連
		if (is_enabled('timeline'))
		{
			// 件数(増えていないことを確認)
			$this->assertEquals(self::$timeline_count, \Util_Orm::get_count_all('\Timeline\Model_Timeline'));
			$this->assertEquals(self::$timeline_cache_count, \Util_Orm::get_count_all('\Timeline\Model_TimelineCache'));

			$timelines = \Timeline\Model_Timeline::get4foreign_table_and_foreign_ids('album', $album->id, \Config::get('timeline.types.album'));
			$this->assertCount(1, $timelines);

			$timeline = array_shift($timelines);
			$this->assertEquals($album->member_id, $timeline->member_id);
			$this->assertEquals($timeline_public_flag_expected, $timeline->public_flag);
			$this->assertContains($timeline->created_at, \Util_Date::get_datetime_list($album->created_at));
			if ($is_changed)
			{
				if (self::check_sort_datetime_change(self::$album, $before))
				{
					$this->assertEquals($album->updated_at, $timeline->sort_datetime);
				}
				$this->assertContains($timeline->updated_at, \Util_Date::get_datetime_list($album->updated_at));
			}

			// timeline view cache check
			if (self::$is_check_timeline_view_cache)
			{
				$this->assertEmpty(\Timeline\Site_Util::get_view_cache($timeline->id));
			}
		}
	}

	public function update_provider()
	{
		$data = array();

		// アルバム編集(変更なし) #0
		$data[] = array(1, array(
			'name' => 'test',
			'body' => 'This is test.',
			'public_flag' => FBD_PUBLIC_FLAG_MEMBER,
		), FBD_PUBLIC_FLAG_MEMBER, FBD_PUBLIC_FLAG_MEMBER);

		// アルバム編集(変更あり) #1
		$data[] = array(1, array(
			'name' => 'test_edit',
			'body' => 'This is test edit.',
			'public_flag' => FBD_PUBLIC_FLAG_PRIVATE,
		), FBD_PUBLIC_FLAG_MEMBER, FBD_PUBLIC_FLAG_PRIVATE);

		// アルバム編集(album_image の public_flag を変更) #2
		$data[] = array(1, array(
			'name' => 'test_edit',
			'body' => 'This is test edit.',
			'public_flag' => FBD_PUBLIC_FLAG_PRIVATE,
			'is_update_children_public_flag' => 1,
		), FBD_PUBLIC_FLAG_PRIVATE, FBD_PUBLIC_FLAG_PRIVATE);

		// アルバム編集(public_flag を拡げる) #3
		$data[] = array(1, array(
			'name' => 'test_edit',
			'body' => 'This is test edit.',
			'public_flag' => FBD_PUBLIC_FLAG_MEMBER,
		), FBD_PUBLIC_FLAG_PRIVATE, FBD_PUBLIC_FLAG_MEMBER);

		// アルバム編集(album と album_image の public_flag を拡げる)
		$data[] = array(1, array(
			'name' => 'test_edit',
			'body' => 'This is test edit.',
			'public_flag' => FBD_PUBLIC_FLAG_ALL,
			'is_update_children_public_flag' => 1,
		), FBD_PUBLIC_FLAG_ALL, FBD_PUBLIC_FLAG_ALL);

		return $data;
	}

	/**
	* @dataProvider update_public_flag_with_relations_provider
	*/
	public function test_update_public_flag_with_relations($public_flag, $is_update_children_public_flag)
	{
		$album = $this->get_album();
		$before = array(
			'public_flag' => $album->public_flag,
			'created_at' => $album->created_at,
			'updated_at' => $album->updated_at,
		);
		$album->update_public_flag_with_relations($public_flag, $is_update_children_public_flag);
		self::$album = $album;

		// 件数
		$this->assertEquals(self::$album_count, \Util_Orm::get_count_all('\Album\Model_Album'));

		// 値
		$this->assertEquals($public_flag, $album->public_flag);

		// date
		// 変更なし
		if ($album->public_flag == $before['public_flag'])
		{
			$this->assertEquals($before['updated_at'], $album->updated_at);
		}
		// 変更あり
		else
		{
			$this->assertTrue(\Util_Date::check_is_future($album->updated_at, $album->created_at));
			$this->assertTrue(\Util_Date::check_is_future($album->updated_at, $before['updated_at']));
		}

		// timeline
		if (is_enabled('timeline'))
		{
			$timelines = \Timeline\Model_Timeline::get4foreign_table_and_foreign_ids('album', $album->id, \Config::get('timeline.types.album'));
			$this->assertCount(1, $timelines);
			$timeline = array_shift($timelines);

			$this->assertEquals($album->public_flag, $timeline->public_flag);
			// 変更なし
			if ($album->public_flag == $before['public_flag'])
			{
				$this->assertEquals($before['updated_at'], $timeline->sort_datetime);
			}
			// 変更あり
			else
			{
				$this->assertEquals($album->updated_at, $timeline->sort_datetime);
			}
		}
	}

	public function update_public_flag_with_relations_provider()
	{
		$data = array();

		// 変更
		$data[] = array(FBD_PUBLIC_FLAG_MEMBER, false);

		// 変更なし
		$data[] = array(FBD_PUBLIC_FLAG_MEMBER, false);

		// album_image も変更
		$data[] = array(FBD_PUBLIC_FLAG_PRIVATE, true);

		return $data;
	}

	public function test_delete_with_relations()
	{
		if (!$album = $this->get_album())
		{
			\Util_Develop::output_test_info(__FILE__, __LINE__);
			$this->markTestSkipped();
		}
		$album_id = $album->id;
		$member_id = $album->member_id;
		$album_filesize_total = self::get_album_filesize_total($album_id);
		$member_filesize_before = self::get_member_filesize_total($member_id);

		$album->delete();

		// 件数
		// album
		$this->assertEquals(self::$album_count - 1, \Util_Orm::get_count_all('\Album\Model_Album'));

		// album_image
		$album_images = Model_AlbumImage::get4album_id($album_id);
		$this->assertEmpty($album_images);

		// filesize
		$this->assertEquals($member_filesize_before - $album_filesize_total, self::get_member_filesize_total($member_id));

		// timeline
		if (is_enabled('timeline'))
		{
			$this->assertEquals(self::$timeline_count - 1, \Util_Orm::get_count_all('\Timeline\Model_Timeline'));
			$this->assertEquals(self::$timeline_cache_count - 2, \Util_Orm::get_count_all('\Timeline\Model_TimelineCache'));

			$timelines = \Timeline\Model_Timeline::get4foreign_table_and_foreign_ids('album', $album_id, \Config::get('timeline.types.album'));
			$this->assertEmpty($timelines);
		}
	}

	private function get_album()
	{
		if (self::$album) return self::$album;

		$upload_file_path = self::setup_upload_file();
		$values = array(
			'name' => 'test',
			'body' => 'This is test.',
			'public_flag' => FBD_PUBLIC_FLAG_MEMBER,
		);
		list($album, $album_image, $file) = self::force_save_album(1, $values, $upload_file_path);
		self::$album = $album;
		$this->set_count();

		return $album;
	}

	private function set_count()
	{
		self::$album_count = \Util_Orm::get_count_all('\Album\Model_Album');
		if (is_enabled('timeline'))
		{
			self::$timeline_count = \Util_Orm::get_count_all('\Timeline\Model_Timeline');
			self::$timeline_cache_count = \Util_Orm::get_count_all('\Timeline\Model_TimelineCache');
		}
	}

	private static function force_save_album($member_id, $values, $upload_file_path, Model_Album $album = null)
	{
		// album save
		if (!$album) $album = Model_Album::forge();
		$album->name = $values['name'];
		$album->body = $values['body'];
		$album->public_flag = $values['public_flag'];
		$album->member_id = $member_id;
		$album->save();

		// album_image save
		$member = \Model_Member::check_authority($member_id);
		list($album_image, $file) = Model_AlbumImage::save_with_relations($album->id, $member, $values['public_flag'], $upload_file_path, 'album');

		return array($album, $album_image, $file);
	}

	private static function setup_upload_file()
	{
		// prepare upload file.
		$original_file = FBD_BASEPATH.'data/development/test/media/img/sample_01.jpg';
		$upload_file = APPPATH.'tmp/sample.jpg';
		\Util_file::copy($original_file, $upload_file);
		chmod($upload_file, 0777);

		return $upload_file;
	}

	private static function get_album_filesize_total($album_id)
	{
		$album_images = Model_AlbumImage::get4album_id($album_id);
		$size = 0;
		foreach ($album_images as $album_image)
		{
			$file = \Model_File::get4name($album_image->file_name);
			$size += $file->filesize;
		}

		return $size;
	}

	private static function get_member_filesize_total($member_id)
	{
		$member = \Model_Member::find($member_id);

		return $member->filesize_total;
	}

	private static function check_sort_datetime_change(Model_Album $album, $before)
	{
		$check_properties = array(
			'name',
			'body',
			'public_flag',
		);
		foreach ($check_properties as $property)
		{
			if ($album->{$property} != $before[$property]) return true;
		}

		return false;
	}
}
