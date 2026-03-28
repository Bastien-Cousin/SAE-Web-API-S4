const { PrismaClient } = require('@prisma/client');

const prisma = new PrismaClient();

async function main() {
  try {
    const teamCount = await prisma.team.count();
    
    if (teamCount === 0) {
      console.log("0");
      process.exit(0);
    } else {
      console.log(teamCount);
      process.exit(0);
    }
  } catch (error) {
    console.error("Error checking database:", error.message);
    process.exit(1);
  } finally {
    await prisma.$disconnect();
  }
}

main();
