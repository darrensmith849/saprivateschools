<div class="tab-content align-center" v-if="currentSubTab === 'other'">
	<div class="form-section">
		<h3>Disable slug update</h3>
		<p>Prevent listing slug from changing when you edit listing title</p>
		<label class="form-switch">
			<input type="checkbox" v-model="settings.disable_slug_update">
			<span class="switch-slider"></span>
		</label>
	</div>
	<div class="form-section">
		<h3>Global listing type</h3>
		<p>
			Use this listing type in Explore page to display a global search form, that will
			look for results within all other listing types. You shouldn't have more than one global listing type.
			They also shouldn't be used in the Add Listing page or anywhere else besides the Explore page.
		</p>
		<label class="form-switch">
			<input type="checkbox" v-model="settings.global">
			<span class="switch-slider"></span>
		</label>
	</div>
	<template v-if="settings.global">
		<div class="form-section">
			<div class="form-group mb10">
				<h3>Advanced settings</h3>
				<p>
					Select this option to enable specific listing types for the global listing type.  This will also provide the ability to choose the default WP post type.
				</p>
				<label class="form-switch">
					<input type="checkbox" v-model="settings.global_custom">
					<span class="switch-slider"></span>
				</label>
			</div>
		</div>
		<div class="form-section" v-if="settings.global_custom">
			<div class="form-group mb10" v-for="type in editor.listing_types_27" v-if="!type.settings.global">
				<label>
					<input class="form-checkbox" type="checkbox" v-model="settings.global_types[type.slug]">
					{{type.settings.plural_name ? type.settings.plural_name : type.slug}}
				</label>
			</div>
			<div class="form-group mb10">
				<label>
					<input type="checkbox" class="form-checkbox" v-model="settings.global_types.c27_posts">
					WP Posts
				</label>
			</div>
		</div>
	</template>
</div>