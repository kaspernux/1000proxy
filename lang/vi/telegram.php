<?php
// Vietnamese translations overriding English baseline. Extend as needed.
return array_replace_recursive(
	include __DIR__ . '/../en/telegram.php',
	[
		'bot' => [
			'name' => '1K PROXY',
			'short' => 'Mua và quản lý proxy ngay trong Telegram',
			'description' => 'Proxy cao cấp và an toàn. Xem gói, quản lý dịch vụ và đơn hàng, nạp tiền ví — tất cả trong Telegram.',
			'menu_text' => 'Mở 1K PROXY',
		],
		'buttons' => [
			'buy_now' => 'Mua ngay',
		],
		'common' => [
			'browse_plans' => 'Xem gói',
			'create_account' => 'Tạo tài khoản',
			'docs' => 'Tài liệu',
			'help' => 'Trợ giúp',
		],
		'messages' => [
			'start_welcome' => "Chào mừng đến 1000proxy! 🚀\n\nBạn có thể xem các gói ngay bây giờ và tạo tài khoản khi sẵn sàng.\n\nNếu muốn liên kết sau qua website:\n1) Truy cập: :url\n2) Đăng nhập hoặc tạo tài khoản\n3) Cài đặt tài khoản → Liên kết Telegram, rồi dán mã vào đây.\n\n",
			'link_intro' => 'Liên kết Telegram của bạn để quản lý tài khoản:',
			'link_steps' => "Cách liên kết tài khoản:\n\n1) Mở: :url\n2) Đăng nhập hoặc tạo tài khoản\n3) Vào Cài đặt tài khoản → Liên kết Telegram\n4) Sao chép mã 8 ký tự và gửi vào đây\n\nBạn có thể dán mã bất kỳ lúc nào.",
			'link_help' => 'Cần trợ giúp? Gõ /help',
		],
		'cmd' => [
			'link' => 'Liên kết Telegram với tài khoản của bạn',
		],
	]
);
