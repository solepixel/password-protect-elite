<?php
/**
 * Password protection page view template.
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
	<?php
	\printf(
		'<title>%s - %s</title>',
		esc_html__( 'Password Required', 'password-protect-elite' ),
		esc_html( $group->name )
	);
	?>
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'ppe-password-page' ); ?>>
	<div class="ppe-password-page-wrapper">
		<div class="ppe-password-page-content">
			<div class="ppe-password-page-header">
				<h1><?php esc_html_e( 'Password Required', 'password-protect-elite' ); ?></h1>
				<?php
				\printf(
					'<p>%s "%s" %s.</p>',
					esc_html__( 'This page is protected. Please enter the password for', 'password-protect-elite' ),
					esc_html( $group->name ),
					esc_html__( 'to continue.', 'password-protect-elite' )
				);
				?>
			</div>

			<div class="ppe-password-page-form">
				<?php echo $form_html; ?>
			</div>

			<?php if ( ! empty( $group->description ) ) : ?>
				<div class="ppe-password-page-description">
					<p><?php echo esc_html( $group->description ); ?></p>
				</div>
			<?php endif; ?>
		</div>
	</div>

	<style>
	.ppe-password-page-wrapper {
		min-height: 100vh;
		display: flex;
		align-items: center;
		justify-content: center;
		background: #f9f9f9;
		padding: 20px;
	}

	.ppe-password-page-content {
		max-width: 400px;
		width: 100%;
		background: white;
		padding: 40px;
		border-radius: 8px;
		box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
		text-align: center;
	}

	.ppe-password-page-header h1 {
		margin: 0 0 10px 0;
		color: #1e1e1e;
		font-size: 24px;
	}

	.ppe-password-page-header p {
		margin: 0 0 30px 0;
		color: #666;
		line-height: 1.5;
	}

	.ppe-password-page-form {
		margin-bottom: 20px;
	}

	.ppe-password-page-description {
		margin-top: 20px;
		padding-top: 20px;
		border-top: 1px solid #eee;
	}

	.ppe-password-page-description p {
		margin: 0;
		color: #666;
		font-size: 14px;
		font-style: italic;
	}

	@media (max-width: 480px) {
		.ppe-password-page-content {
			padding: 30px 20px;
		}
	}
	</style>

	<?php wp_footer(); ?>
</body>
</html>

