<template>
	<div>
		<input id="sharingToken"
			type="hidden"
			name="sharingToken"
			:value="shareTokenParam">
		<NcEmptyContent v-show="loading"
			:title="t('collectives', 'Preparing collective for exporting or printing')">
			<template #icon>
				<DownloadIcon />
			</template>
			<template #action>
				<NcProgressBar :value="loadingProgress" size="medium">
					{{ loadingProgress }}
				</NcProgressBar>
				<ul class="load-messages">
					<li v-for="task in [loadPages, loadImages]"
						v-show="task.total"
						:key="task.message">
						{{ task.message }}
						{{ task.total ? `${task.count} / ${task.total}` : '' }}
					</li>
				</ul>
			</template>
		</NcEmptyContent>
		<div v-for="page in pagesTreeWalk()" v-show="!loading" :key="page.id">
			<PagePrint :page="page"
				@loading="waitingFor.push(page.id)"
				@ready="ready(page.id)" />
		</div>
	</div>
</template>

<script>
import { mapGetters, mapActions } from 'vuex'
import { NcEmptyContent } from '@nextcloud/vue'
import NcProgressBar from '@nextcloud/vue/dist/Components/NcProgressBar.js'
import DownloadIcon from 'vue-material-design-icons/Download.vue'
import debounce from 'debounce'
import PagePrint from './PagePrint.vue'
import { GET_PAGES } from '../store/actions.js'
import displayError from '../util/displayError.js'

export default {
	name: 'CollectivePrint',

	components: {
		NcEmptyContent,
		PagePrint,
		DownloadIcon,
		NcProgressBar,
	},

	data() {
		return {
			loading: true,
			waitingFor: [],
			loadPages: {
				message: t('collectives', 'Loading pages:'),
				count: 0,
				total: 0,
			},
			loadImages: {
				message: t('collectives', 'Loading images:'),
				count: 0,
				total: 0,
			},
		}
	},

	computed: {
		...mapGetters([
			'currentCollective',
			'pagesTreeWalk',
			'shareTokenParam',
		]),

		loadingCount() {
			return this.loadPages.count + this.loadImages.count
		},

		loadingTotal() {
			return this.loadPages.total + this.loadImages.total
		},

		loadingProgress() {
			return this.loadingTotal
				? this.loadingCount / this.loadingTotal * 100
				: 0
		},

		documentTitle() {
			const parts = [
				this.currentCollective.name,
				t('collectives', 'Collectives'),
				'Nextcloud',
			]

			return parts.join(' - ')
		},
	},

	mounted() {
		this.getPages()
	},

	methods: {
		...mapActions({
			dispatchGetPages: GET_PAGES,
		}),

		/**
		 * Get list of all pages
		 */
		async getPages() {
			await this.dispatchGetPages()
				.catch(displayError('Could not fetch pages'))
			this.loadPages.total = this.pagesTreeWalk().length
		},

		ready(pageId) {
			if (this.waitingFor.indexOf(pageId) >= 0) {
				this.waitingFor.splice(this.waitingFor.indexOf(pageId), 1)
				this.loadPages.count += 1
			}
			if (!this.waitingFor.length) {
				this.$nextTick(this.waitForImages)
			}
		},

		waitForImages() {
			const images = document.querySelectorAll('div.ProseMirror figure.image')
			const loading = document.querySelectorAll('div.ProseMirror figure.image.icon-loading')
			this.loadImages.total = images.length
			this.loadImages.count = images.length - loading.length

			if (!loading.length) {
				this.allImagesLoaded()
			}

			for (const el of loading) {
				// Hook into the capture phase as `load` events do not bubble up.
				el.addEventListener('load', this.imageLoaded, { capture: true })
			}

			// Wait 1 sec for each image (min 3 sec, max. 15 sec), timeout afterwards
			const timeout = Math.min(Math.max(loading.length * 1000, 3000), 15000)
			this.$imageTimeout = debounce(() => {
				if (this.loadImages.count < this.loadImages.total) {
					console.error(`Failed to load ${this.loadImages.total - this.loadImages.count} images`)
					this.allImagesLoaded()
				}
			}, timeout)
			this.$imageTimeout()
		},

		imageLoaded(event) {
			if (!event.target.classList.contains('image__main')) {
				return
			}
			this.loadImages.count += 1
			if (this.loadImages.count >= this.loadImages.total && this.loading) {
				this.$imageTimeout?.clear()
				// Finish loading the image
				this.$nextTick(() => {
					setTimeout(this.allImagesLoaded, 100)
				})
			}
		},

		allImagesLoaded() {
			this.loading = false
			this.$nextTick(() => {
				// Scroll back to the beginning of the document
				document.getElementById('content-vue').scrollIntoView()
				document.title = this.documentTitle

				window.print()
			})
		},
	},
}
</script>

<style scoped>
.progress-bar {
	margin-top: 8px;
}

.load-messages {
	color: var(--color-text-maxcontrast);
}
</style>
