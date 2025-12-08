# create sine function lists to be used as lookup tables
import numpy as np

x = np.linspace(0, 2*np.pi, 100, endpoint=False, dtype=float)

y1 = []
y2 = []
y3 = []

for i in x:
    y1.append(hex(round(32767.5*np.sin(i+((3*np.pi)/2)) + 32767.5)))

for i in x:
    y2.append(hex(round(32767.5*np.sin(i+((np.pi)/6)) + 32767.5)))

for i in x:
    y3.append(hex(round(32767.5*np.sin(i+((5*np.pi)/6)) + 32767.5)))

for i in range(0, 100):
    print(y1[i], end = ", ")

print("")
print("")


for i in range(0, 100):
    print(y2[i], end = ", ")

print("")
print("")


for i in range(0, 100):
    print(y3[i], end = ", ")
