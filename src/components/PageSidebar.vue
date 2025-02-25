<template>
	<NcAppSidebar ref="sidebar"
		:title="title"
		:active.sync="active"
		@close="close">
		<NcAppSidebarTab id="attachments"
			:order="0"
			:name="t('collectives', 'Attachments')">
			<template #icon>
				<PaperclipIcon :size="20" />
			</template>
			<SidebarTabAttachments v-if="showing('sidebar')" :page="currentPage" />
		</NcAppSidebarTab>
		<NcAppSidebarTab id="backlinks"
			:order="1"
			:name="t('collectives', 'Backlinks')">
			<template #icon>
				<ArrowBottomLeftIcon :size="20" />
			</template>
			<SidebarTabBacklinks v-if="showing('sidebar')" :page="currentPage" />
		</NcAppSidebarTab>
		<NcAppSidebarTab v-if="!isPublic && currentCollectiveCanShare"
			id="sharing"
			:order="2"
			:name="t('collectives', 'Sharing')">
			<template #icon>
				<ShareVariantIcon :size="20" />
			</template>
			<SidebarTabSharing v-if="showing('sidebar')" :page-id="currentPage.id" />
		</NcAppSidebarTab>
		<NcAppSidebarTab v-if="!isPublic && currentCollectiveCanEdit"
			id="versions"
			:order="3"
			:name="t('collectives', 'Versions')">
			<template #icon>
				<RestoreIcon :size="20" />
			</template>
			<SidebarTabVersions v-if="showing('sidebar')"
				:page-id="currentPage.id"
				:page-timestamp="currentPage.timestamp"
				:page-size="currentPage.size" />
		</NcAppSidebarTab>
	</NcAppSidebar>
</template>

<script>
import { mapGetters, mapMutations } from 'vuex'
import { NcAppSidebar, NcAppSidebarTab } from '@nextcloud/vue'
import RestoreIcon from 'vue-material-design-icons/Restore.vue'
import ArrowBottomLeftIcon from 'vue-material-design-icons/ArrowBottomLeft.vue'
import PaperclipIcon from 'vue-material-design-icons/Paperclip.vue'
import ShareVariantIcon from 'vue-material-design-icons/ShareVariant.vue'
import { SELECT_VERSION } from '../store/mutations.js'
import SidebarTabAttachments from './PageSidebar/SidebarTabAttachments.vue'
import SidebarTabBacklinks from './PageSidebar/SidebarTabBacklinks.vue'
import SidebarTabSharing from './PageSidebar/SidebarTabSharing.vue'
import SidebarTabVersions from './PageSidebar/SidebarTabVersions.vue'

export default {
	name: 'PageSidebar',

	components: {
		NcAppSidebar,
		NcAppSidebarTab,
		RestoreIcon,
		ArrowBottomLeftIcon,
		PaperclipIcon,
		ShareVariantIcon,
		SidebarTabAttachments,
		SidebarTabBacklinks,
		SidebarTabSharing,
		SidebarTabVersions,
	},

	data() {
		return {
		}
	},

	computed: {
		...mapGetters([
			'activeSidebarTab',
			'currentCollectiveCanEdit',
			'currentCollectiveCanShare',
			'currentPage',
			'isPublic',
			'showing',
			'title',
		]),

		active: {
			get() {
				return this.activeSidebarTab
			},
			set(id) {
				this.setActiveSidebarTab(id)
			},
		},
	},

	methods: {
		...mapMutations(['hide', 'setActiveSidebarTab']),

		/**
		 * Load the current version and close the sidebar
		 */
		close() {
			this.$store.commit(SELECT_VERSION, null)
			this.hide('sidebar')
		},
	},
}
</script>

<style>
.app-sidebar-tab-desc {
	font-weight: bold;
}
</style>
