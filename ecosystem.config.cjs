// Dev HMR (default): pm2 delete ccgame-muh5 && pm2 start ecosystem.config.cjs --update-env
// Production: bun run build && pm2 start ecosystem.production.cjs --update-env

module.exports = {
  apps: [
    {
      name: 'ccgame-muh5',
      cwd: __dirname,
      script: 'bun',
      args: 'run dev -- --host 0.0.0.0 --port 4100',
      interpreter: 'none',
      autorestart: true,
      max_memory_restart: '768M',
      env: {
        NODE_ENV: 'development',
        NUXT_VITE_DEV_ORIGIN: 'https://muh5.ccgame.org',
        HOST: '0.0.0.0',
        PORT: '4100',
      },
      env_production: {
        NODE_ENV: 'production',
        HOST: '0.0.0.0',
        PORT: '4100',
      },
    },
  ],
}
