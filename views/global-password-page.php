<?php
/**
 * Global password protection page view template.
 *
 * @package PasswordProtectElite
 */

// Prevent direct access.
if ( ! \defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php esc_html_e( 'Site Access Required', 'password-protect-elite' ); ?></title>
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'ppe-global-password-page' ); ?>>
	<div class="ppe-global-password-wrapper">
		<div class="ppe-global-password-content">
			<div class="ppe-global-password-header">
				<h1><?php echo esc_html( get_bloginfo( 'name' ) ); ?></h1>
				<p><?php esc_html_e( 'This site is password protected. Please enter the site password to continue.', 'password-protect-elite' ); ?></p>
			</div>

			<div class="ppe-global-password-form">
				<?php echo $form_html; ?>
			</div>

			<div class="ppe-global-password-footer">
				<p><?php esc_html_e( 'If you have forgotten the password, please contact the site administrator.', 'password-protect-elite' ); ?></p>
			</div>
		</div>
	</div>

	<style>
	.ppe-global-password-wrapper {
		min-height: 100vh;
		display: flex;
		align-items: center;
		justify-content: center;
		background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
		padding: 20px;
	}

	.ppe-global-password-content {
		max-width: 450px;
		width: 100%;
		background: white;
		padding: 50px;
		border-radius: 12px;
		box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
		text-align: center;
	}

	.ppe-global-password-header h1 {
		margin: 0 0 15px 0;
		color: #1e1e1e;
		font-size: 28px;
		font-weight: 600;
	}

	.ppe-global-password-header p {
		margin: 0 0 40px 0;
		color: #666;
		line-height: 1.6;
		font-size: 16px;
	}

	.ppe-global-password-form {
		margin-bottom: 30px;
	}

	.ppe-global-password-footer {
		margin-top: 30px;
		padding-top: 20px;
		border-top: 1px solid #eee;
	}

	.ppe-global-password-footer p {
		margin: 0;
		color: #999;
		font-size: 14px;
		line-height: 1.5;
	}

	.ppe-global-password-form .ppe-password-form {
		background: transparent;
		border: none;
		padding: 0;
	}

	.ppe-global-password-form .ppe-password-input {
		border: 2px solid #e1e5e9;
		border-radius: 8px;
		padding: 15px;
		font-size: 16px;
		transition: border-color 0.3s ease;
	}

	.ppe-global-password-form .ppe-password-input:focus {
		border-color: #667eea;
		box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
	}

	.ppe-global-password-form .ppe-submit-button {
		background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
		border: none;
		border-radius: 8px;
		padding: 15px 30px;
		font-size: 16px;
		font-weight: 600;
		transition: transform 0.2s ease, box-shadow 0.2s ease;
	}

	.ppe-global-password-form .ppe-submit-button:hover {
		transform: translateY(-2px);
		box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
	}

	@media (max-width: 480px) {
		.ppe-global-password-content {
			padding: 40px 30px;
		}

		.ppe-global-password-header h1 {
			font-size: 24px;
		}
	}
	</style>

	<?php wp_footer(); ?>
</body>
</html>

