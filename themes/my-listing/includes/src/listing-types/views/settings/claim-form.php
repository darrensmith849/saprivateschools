<claim-form inline-template v-if="currentSubTab === 'claim-form'">
	<div class="tab-content settings-packages full-width">
		<div class="form-section">
			<h3>Select or create claim fields for this listing type</h3>
		</div>

		<div class="editor-column col-2-3 rows row-padding">
			<div class="form-section mb10">
				<h4 class="mb5">Used fields</h4>
				<p>Click on a field to edit. Drag & Drop to reorder.</p>
			</div>

			<draggable v-model="$root.settings.claim_form.used" :options="{group: 'claim-forms', handle: '.row-head'}">
				<div v-for="field in $root.settings.claim_form.used" class="row-item" :class="field === activeClaim ? 'open' : ''">
					<div @click="activeClaim = (field !== activeClaim) ? field : null" class="row-head">
						<div class="row-head-toggle"><i class="mi chevron_right"></i></div>
						<div class="row-head-label">
							<h4>{{ field.label }}</h4>
							<div class="details">
								<div class="detail">{{ field.slug }}</div>
							</div>
						</div>
						<div class="row-head-actions">
							<span title="Remove" @click.stop="deleteField(field.slug)" class="action red"><i class="mi delete"></i></span>
						</div>
					</div>
					<div class="row-edit" v-if="activeClaim === field">
						<?php foreach ( \MyListing\Src\Claims\get_field_types() as $field_type ): ?>
							<?php echo $field_type->print_editor_options() ?>
						<?php endforeach ?>
						<div class="text-right">
							<div class="btn" @click="activeClaim = null">Done</div>
						</div>
					</div>
				</div>
			</draggable>
		</div><!--
		--><div class="editor-column col-1-3">
			<div class="form-section mb10">
				<h4 class="mb5">Preset fields</h4>
				<p>Click on the field type you want to create.</p>
			</div>

			<div class="btn btn-secondary btn-block mb10" @click="addCustomField('text')">Text</div>
			<div class="btn btn-secondary btn-block mb10" @click="addCustomField('textarea')">Textarea</div>
			<div class="btn btn-secondary btn-block mb10" @click="addCustomField('number')">Number</div>
			<div class="btn btn-secondary btn-block mb10" @click="addCustomField('file')">File Upload</div>
		</div>
	</div>
</claim-form>