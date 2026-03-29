/*
  Warnings:

  - A unique constraint covering the columns `[objectname]` on the table `match` will be added. If there are existing duplicate values, this will fail.

*/
-- CreateIndex
CREATE UNIQUE INDEX "match_objectname_key" ON "match"("objectname");
