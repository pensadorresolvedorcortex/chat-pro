const names = {
  'usuario-demo': 'Patrícia',
};

async function getName(userId) {
  return names[userId] || 'Cliente';
}

module.exports = { getName };
