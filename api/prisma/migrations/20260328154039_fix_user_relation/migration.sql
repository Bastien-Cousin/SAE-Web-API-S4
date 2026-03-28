-- CreateTable
CREATE TABLE "map" (
    "id" SERIAL NOT NULL,
    "name" TEXT NOT NULL,
    "competition_date" TEXT,
    "image_url" TEXT,
    "created_at" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMP(3) NOT NULL,

    CONSTRAINT "map_pkey" PRIMARY KEY ("id")
);

-- CreateIndex
CREATE UNIQUE INDEX "map_name_key" ON "map"("name");
