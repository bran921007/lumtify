<style></style>

<template>
<v-layout row wrap>
    <v-flex xs12 class="text-xs-center" v-if="loading">
    	<v-progress-circular 
		    indeterminate 
		    v-bind:size="50" 
		    class="primary--text" 
		  />
    </v-flex>
    <v-flex xs12 v-else-if="!loading">
	    <h1>
	        {{ name }}
		</h1>
	    <h3>{{ email }}</h3>
	    <v-row>
	    	<v-btn class="white--text" primary small v-on:click.native="setting">
	    	    Setting
            </v-btn>
	    </v-row>
    </v-flex>
</v-layout>
</template>

<script>
import { mapActions } from 'vuex'

export default {
	data () {
		return {
			name: '',
			email: '',
			uid: '',
			loading: true
		}
	},
	created () {
        this.fetch()
    },
	methods: {
		...mapActions(['notify']),
		fetch () {
			this.loading = true
			this.$http.get('/api/users/' + this.$route.params.uid).then((res) => {
				var data = res.body

				if (data.success) {
					this.uid = data.user.uid
					this.name = data.user.name
					this.email = data.user.email
				}
			}).catch((err) => {
				if (!err.success) {
					this.notify({ msg: err.msg, show: true })
				}
				this.$router.push({ name: 'home' })
			}).then(() => {
				this.loading = false
			})
		},
		setting () {
			if (!this.uid) {
				return
			}
			this.$router.push({name: 'setting', params: {uid: this.uid}})
		}
	},
	watch: {
		'$route': 'fetch'
	}
}
</script>