<style></style>

<template>
<v-col xs4="xs4">
    <div>
        <div>
            <h5>Login</h5>
        </div>
        <div>
	        <v-alert v-bind:info="!info" v-bind:error="!success" v-bind:value="true" v-if="msg">
			    {{ msg }}
			</v-alert>
		</div>
        <div>
		    <v-text-field 
			    name="email"
			    label="Email"
			    type="email"
			    v-model="email"
			    dark
			    prepend-icon="email"
			></v-text-field>
			<span class="red--text" v-if="errFor.email">{{ errFor.email.join(",") }}</span>
		</div>
        <div>
		    <v-text-field 
			    name="password"
			    label="Password"
			    type="password"
			    v-model="password"
			    dark
			    prepend-icon="lock"
			></v-text-field>
			<span class="red--text" v-if="errFor.password">{{ errFor.password.join(",") }}</span>
		</div>
        <div>
            <v-btn 
                info
                v-bind:disabled="loading"
                v-on:click.native="login"
                small
            >
                Login
            </v-btn>
        </div>
    </div>
</v-col>
</template>

<script>
import { mapActions } from 'vuex'

export default {
	data () {
		return {
			email: '',
			password: '',
			errFor: {},
			success: false,
			loading: false,
			msg: '',
		}
	},
	methods: {
		...mapActions(['notify']),
		login () {
			this.loading = true
			this.$http.post('/api/auth/login', {
				email: this.email,
				password: this.password
			}).then((res) => {
				var data = res.body

				if (data.success) {
					this.errFor = data.errFor
					this.errs = data.errs
					this.msg = data.msg
					this.success = data.success
					localStorage.setItem('lumtify', data.token)
					this.notify({ msg: data.msg, show: true })
					this.$router.push({ name: 'home' })
				}
			}).catch((err) => {
				var e = err.body

				if (!e.success) {
					this.errFor = e.errFor
					this.errs = e.errs
					this.msg = e.msg
					this.success = e.success
					this.notify({ msg: e.msg, show: true })
				} else {
					this.$router.push({ name: 'home' })
				}
			}).then(() => {
				this.loading = false
			})
		}
	}
}
</script>