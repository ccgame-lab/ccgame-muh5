// Production PM2 only — run after `bun run build`
//   pm2 startOrRestart ecosystem.production.cjs --update-env

module.exports = {
  apps: [
    {
      name: 'ccgame-muh5',
      cwd: __dirname,
      script: '.output/server/index.mjs',
      interpreter: 'bun',
      autorestart: true,
      max_memory_restart: '768M',
      env: {
        NODE_ENV: 'production',
        HOST: '0.0.0.0',
        PORT: '4100',
      },
    },
  ],
}
