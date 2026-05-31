import type { Metadata } from "next";
import "./globals.css";

export const metadata: Metadata = {
  title: "Wexoe Page Builder",
  description: "Visuell sidbyggare för Wexoe landing pages",
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="sv" className="h-full">
      <body className="h-full">{children}</body>
    </html>
  );
}
