<?php
$config = array(
	'mail' => array(
		'site' => array(
			'common_variables' => array(
				'to_email' => '宛先メールアドレス',
				'site_name' => 'サイト名',
				'site_description' => 'サイト説明',
				'base_url' => 'サイトURL',
				'admin_mail' => 'サイト管理者メールアドレス',
				'admin_name' => 'サイト管理者名',
				'admin_company_name' => 'サイト管理者会社名(英語)',
				'admin_company_name_jp' => 'サイト管理者会社名(日本語)',
			),
			'signature' => array(
				'view' =>'署名',
				'format' =>'twig',
				'body' => array(
					'default' => array(
						'file' => 'mail/signature',
					),
				),
			),
			'memberSignup' => array(
				'view' =>'メンバー仮登録完了お知らせメール',
				'format' =>'twig',
				'title' => '仮登録完了のお知らせ',
				'body' => array(
					'default' => array(
						'file' => 'mail/member_signup',
					),
				),
				'variables' => array(
					'register_url' => '登録用URL',
				),
			),
			'memberRegister' => array(
				'view' =>'メンバー登録完了お知らせメール',
				'format' =>'twig',
				'title' => 'メンバー登録完了のお知らせ',
				'body' => array(
					'default' => array(
						'file' => 'mail/member_register',
					),
				),
				'variables' => array(
					'to_name' => '登録したニックネーム',
					'to_email' => '登録したメールアドレス',
				),
			),
			'memberLeave' => array(
				'view' =>'メンバー退会完了お知らせメール',
				'format' =>'twig',
				'title' => 'メンバー退会完了のお知らせ',
				'body' => array(
					'default' => array(
						'file' => 'mail/member_leave',
					),
				),
				'variables' => array(
					'to_name' => 'メンバー名',
				),
			),
			'memberSettingPassword' => array(
				'view' =>'パスワード変更完了のお知らせメール',
				'format' =>'twig',
				'title' => 'パスワード変更完了のお知らせ',
				'body' => array(
					'default' => array(
						'file' => 'mail/member_setting_password',
					),
				),
				'variables' => array(
					'to_name' => 'メンバー名',
				),
			),
			'memberRegisterEmailConfirm' => array(
				'view' =>'メールアドレス登録確認メール',
				'format' =>'twig',
				'title' => 'メールアドレス登録確認',
				'body' => array(
					'default' => array(
						'file' => 'mail/member_register_email_confirm',
					),
				),
				'variables' => array(
					'to_name' => 'メンバー名',
					'confirmation_code' => '確認用コード',
				),
			),
			'memberChangeEmail' => array(
				'view' =>'メールアドレス変更完了お知らせメール',
				'format' =>'twig',
				'title' => 'メールアドレス変更完了のお知らせ',
				'body' => array(
					'default' => array(
						'file' => 'mail/member_change_email',
					),
				),
				'variables' => array(
					'to_name' => 'メンバー名',
				),
			),
			'memberResendPassword' => array(
				'view' =>'パスワードの再登録確認メール',
				'format' =>'twig',
				'title' => 'パスワードの再登録確認',
				'body' => array(
					'default' => array(
						'file' => 'mail/member_resend_password',
					),
				),
				'variables' => array(
					'to_name' => 'メンバー名',
					'register_url' => '登録用URL',
				),
			),
			'memberResetPassword' => array(
				'view' =>'パスワード再登録完了お知らせメール',
				'format' =>'twig',
				'title' => 'パスワードの再登録完了のお知らせ',
				'body' => array(
					'default' => array(
						'file' => 'mail/member_reset_password',
					),
				),
				'variables' => array(
					'to_name' => 'メンバー名',
				),
			),
			'memberInvite' => array(
				'view' =>'招待メール',
				'format' =>'twig',
				'title' => '{{ invite_member_name }} から {{ site_name }} の招待状が届いています',
				'body' => array(
					'default' => array(
						'file' => 'mail/member_invite',
					),
				),
				'variables' => array(
					'register_url' => '登録用URL',
					'invite_member_name' => '招待者ニックネーム',
					'invite_message' => '招待メッセージ',
				),
			),
		),
	),
);
if (FBD_INTERNATIONALIZED_DOMAIN)
{
	Arr::set($config, 'mail.site.common_variables.idn_url', 'サイトURL(国際化ドメイン)');
}
$config = Site_Config::merge_module_configs($config, 'template');

try
{
	$config = Site_Config::setup_configs_template($config, 'template');
}
catch(Database_Exception $e)
{
	// Task DbSetter 実行時にDBが存在しない場合があるので、スルーする
}

return $config;
