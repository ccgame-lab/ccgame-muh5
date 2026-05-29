// Production PM2 — artifact deploy via scripts/deploy.sh
// File này được copy lên /opt/ccgame-muh5/shared/ecosystem.config.cjs khi deploy.
// cwd cố định sang symlink current → rollback chỉ cần đổi symlink.

module.exports = {
	apps: [
		{
			name: 'ccgame-muh5',
			cwd: '/opt/ccgame-muh5/current',
			script: '.output/server/index.mjs',
			interpreter: 'bun',
			autorestart: true,
			max_memory_restart: '768M',
			restart_delay: 3000,
			max_restarts: 10,
			env: {
				NODE_ENV: 'production',
				HOST: '127.0.0.1',
				PORT: '4100',
			},
		},
	],
};
