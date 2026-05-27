module.exports = {
  apps: [
    {
      name: 'ccgame-muh5',
      cwd: __dirname,
      script: '.output/server/index.mjs',
      interpreter: 'bun',
      exec_mode: 'fork',
      instances: 1,
      autorestart: true,
      env: {
        NODE_ENV: 'production',
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
